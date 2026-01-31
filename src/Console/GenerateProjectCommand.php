<?php

namespace quintenmbusiness\LaravelProjectGeneration\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use quintenmbusiness\LaravelProjectGeneration\ClassGeneration\GenerationService;
use quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\FactoryGenerator;
use quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\ModelGenerator;
use quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\Tests\ModelUnitTestsGenerator;
use Symfony\Component\Console\Helper\Table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiSelect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\note;

class GenerateProjectCommand extends Command
{
    protected $signature = 'project:generate';

    protected $description = 'Generate project structure and classes based on database schema';

    protected string $connection;
    protected string $previewMode;
    protected array $classTypes = [];
    protected bool $showFileTree = false;
    protected bool $runTestsAfterGeneration = false;
    protected array $availableConnections = [];

    public function handle(): int
    {
        $this->intro();

        $this->collectDatabaseSchema();
        $this->askDatabaseConnection();
        $this->askPreviewMode();
        $this->askClassTypes();
        $this->askFileTreePreference();
        $this->safetyWarnings();

        if ($this->shouldAskTestExecution()) {
            $this->askRunTests();
        }

        $this->runGeneration();

        if ($this->runTestsAfterGeneration) {
            $this->runGeneratedTests();
        }

        $this->success();

        return self::SUCCESS;
    }

    protected function intro(): void
    {
        note('Laravel Project Generation Wizard');
    }

    protected function collectDatabaseSchema(): void
    {
        $connections = Config::get('database.connections', []);

        // Only include sqlite or mysql driver connections
        $this->availableConnections = collect($connections)
            ->filter(fn ($config) => in_array($config['driver'] ?? '', ['sqlite', 'mysql'], true))
            ->keys()
            ->values()
            ->toArray();

        if (empty($this->availableConnections)) {
            $this->error('No sqlite or mysql database connections found.');
            exit(self::FAILURE);
        }
    }

    protected function askDatabaseConnection(): void
    {
        $default = Config::get('database.default');

        if (! in_array($default, $this->availableConnections, true)) {
            $default = $this->availableConnections[0];
        }

        $this->connection = select(
            label: 'Select database connection',
            options: $this->availableConnections,
            default: $default
        );
    }


    protected function askPreviewMode(): void
    {
        $this->previewMode = select(
            label: 'Select preview detail level',
            options: [
                'A' => 'All tables, columns and relationships',
                'B' => 'All tables and columns',
                'C' => 'Only tables',
                'D' => 'Nothing',
            ], default: 'A'
        );
    }

    protected function askClassTypes(): void
    {
        $this->classTypes = multiSelect(
            label: 'Which classes should be generated?',
            options: [
                ModelGenerator::class => 'Model',
                FactoryGenerator::class => 'Factory',
                ModelUnitTestsGenerator::class => 'ModelTest',
            ],
            default: [
                'Model',
                'Factory',
                'ModelTest',
            ],
            required: true
        );

        $this->renderClassSummary();
    }

    protected function renderClassSummary(): void
    {
        $this->line('');
        $this->info('Selected generators');

        $table = new Table($this->output);
        $table->setHeaders(['Class Type', 'Generate']);

        foreach ($this->classTypes as $type) {
            $table->addRow([$type, 'Yes']);
        }

        $table->render();
    }

    protected function askFileTreePreference(): void
    {
        $this->showFileTree = confirm(
            label: 'Show generated file tree before continuing?',
            default: true
        );

        if ($this->showFileTree) {
            $this->showFileTreePreview();
        }
    }

    protected function safetyWarnings(): void
    {
        note('Existing files WILL be overwritten');

        if (! confirm('Do you understand this?', default: true)) {
            $this->abort();
        }

        if (! confirm('Have you committed or backed up your code?', default: true)) {
            $this->abort();
        }

        if (! confirm('Final confirmation: proceed with generation?', default: true)) {
            $this->abort();
        }
    }

    protected function shouldAskTestExecution(): bool
    {
        return in_array('Tests', $this->classTypes, true);
    }

    protected function askRunTests(): void
    {
        $this->runTestsAfterGeneration = confirm(
            label: 'JK not the final warning. Run generated tests after generation?',
            default: false
        );
    }

    protected function runGeneration(): void
    {
        note('Generating project');

        $this->writeFiles();
    }

    protected function writeFiles(): array
    {
        return (new GenerationService($this->connection))->generateProject($this->classTypes);
    }

    protected function runGeneratedTests(): void
    {
        note('Running generated tests');
    }

    protected function showFileTreePreview(): void
    {
        $this->line('');
        $this->info('Planned file structure');
        $this->line('');

        $hasAppContent      = in_array(ModelGenerator::class, $this->classTypes);
        $hasDatabaseContent = in_array(FactoryGenerator::class, $this->classTypes);

        $this->line('Root');

        if ($hasAppContent) {
            $this->line('├── app');

            if (in_array(ModelGenerator::class, $this->classTypes)) {
                $this->line('│   └── Models');
            }
        }

        if ($hasDatabaseContent) {
            $this->line('├── database');
            $this->line('│   └── factories');
        }
    }


    protected function abort(): void
    {
        $this->error('Generation aborted');
        exit(self::FAILURE);
    }

    protected function success(): void
    {
        note('Generation completed successfully');
    }
}
