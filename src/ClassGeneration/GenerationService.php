<?php

namespace quintenmbusiness\LaravelProjectGeneration\ClassGeneration;

use quintenmbusiness\LaravelAnalyzer\Modules\Database\DatabaseModule;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\DatabaseDTO;
use quintenmbusiness\LaravelProjectGeneration\DataLayerGeneration\ModelGenerator;
use Symfony\Component\Process\Process;
class GenerationService
{
    public DatabaseDTO $database;

    public function __construct()
    {
        $this->database = (new DatabaseModule())->getDatabase();
    }

    public function generateProject(): void
    {
        foreach($this->database->tables as $table) {
            (new ModelGenerator($table))->generate();
        }

        $this->runPhpCsFixer();
    }

    public function runPhpCsFixer(string $targetPath = null): void
    {
        $targetPath = $targetPath ?? base_path();
        $targetPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $targetPath);

        $configPath = base_path('.php-cs-fixer.php');

        $regenerateConfig = true;

        if (file_exists($configPath)) {
            $existing = file_get_contents($configPath);
            if ($existing !== false && strpos($existing, '@Laravel') === false) {
                $regenerateConfig = false;
            }
        }

        if ($regenerateConfig) {
            file_put_contents(
                $configPath,
                "<?php

use PhpCsFixer\\Config;
use PhpCsFixer\\Finder;

\$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'storage', 'bootstrap/cache']);

return (new Config())
    ->setRiskyAllowed(true)
    ->setUsingCache(true)
    ->setRules([
        '@PhpCsFixer' => true,
        '@PhpCsFixer:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'binary_operator_spaces' => ['default' => 'single_space'],
        'blank_line_before_statement' => ['statements' => ['return']],
        'concat_space' => ['spacing' => 'one'],
        'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
        'no_unused_imports' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'single_import_per_statement' => true,
        'single_trait_insert_per_statement' => true,
        'visibility_required' => true,
        'yoda_style' => false,
    ])
    ->setFinder(\$finder);
"
            );
        }

        $binPath = realpath(__DIR__ . '/../..') . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR;

        if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
            $phpCsFixer = $binPath . 'php-cs-fixer.bat';
            if (!file_exists($phpCsFixer)) {
                $phpCsFixer = $binPath . 'php-cs-fixer';
            }
            if (!file_exists($phpCsFixer)) {
                return;
            }

            $command = [
                $phpCsFixer,
                'fix',
                $targetPath,
                '--config=' . $configPath,
                '--allow-risky=yes',
                '--using-cache=yes',
                '--allow-unsupported-php-version=yes',
                '--show-progress=none',
            ];
        } else {
            $phpCsFixer = $binPath . 'php-cs-fixer';
            if (!file_exists($phpCsFixer)) {
                return;
            }

            $command = [
                PHP_BINARY,
                $phpCsFixer,
                'fix',
                $targetPath,
                '--config=' . $configPath,
                '--allow-risky=yes',
                '--using-cache=yes',
                '--allow-unsupported-php-version=yes',
                '--show-progress=none',
            ];
        }

        $process = new Process($command);
        $process->run();
    }




}