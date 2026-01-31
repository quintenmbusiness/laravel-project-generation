<?php

namespace quintenmbusiness\LaravelProjectGeneration\ClassGeneration;

use quintenmbusiness\LaravelAnalyzer\Modules\Database\DatabaseModule;
use quintenmbusiness\LaravelAnalyzer\Modules\Database\DTO\DatabaseDTO;
use Symfony\Component\Process\Process;
class GenerationService
{
    public DatabaseDTO $database;

    public function __construct(public string|null $connection = null)
    {
        $this->database = (new DatabaseModule())->getDatabase($connection);
    }

    public function generateProject(array $classesToGenerate): array
    {
        foreach($this->database->tables as $table) {
            foreach ($classesToGenerate as $class) {
                (new $class($table, $classesToGenerate))->generate();
            }
        }

        $this->runPhpCsFixer();
        return [];
    }

    public function runPhpCsFixer(string $targetPath = null): void
    {
        try {
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

            $phpCsFixer = null;

            if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
                $windowsBat = $binPath . 'php-cs-fixer.bat';
                $windowsExe = $binPath . 'php-cs-fixer';

                if (file_exists($windowsBat)) {
                    $phpCsFixer = $windowsBat;
                } elseif (file_exists($windowsExe)) {
                    $phpCsFixer = $windowsExe;
                }
            } else {
                $unixPath = $binPath . 'php-cs-fixer';
                if (file_exists($unixPath)) {
                    $phpCsFixer = $unixPath;
                }
            }

            if ($phpCsFixer === null) {
                $projectBin = base_path('vendor/bin/php-cs-fixer');
                $projectBinBat = base_path('vendor/bin/php-cs-fixer.bat');

                if (file_exists($projectBin)) {
                    $phpCsFixer = $projectBin;
                } elseif (file_exists($projectBinBat)) {
                    $phpCsFixer = $projectBinBat;
                }
            }

            if ($phpCsFixer === null) {
                return;
            }

            $command = [];

            if (strncasecmp(PHP_OS, 'WIN', 3) === 0) {
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

            try {
                $process = new Process($command);
                $process->run();

                if (!$process->isSuccessful()) {
                    $fallback = base_path('vendor/bin/php-cs-fixer');

                    if (file_exists($fallback)) {
                        $fallbackCommand = [
                            PHP_BINARY,
                            $fallback,
                            'fix',
                            $targetPath,
                            '--config=' . $configPath,
                            '--allow-risky=yes',
                            '--using-cache=yes',
                            '--allow-unsupported-php-version=yes',
                            '--show-progress=none',
                        ];

                        $fallbackProcess = new Process($fallbackCommand);
                        $fallbackProcess->run();
                    }
                }
            } catch (\Throwable $e) {
                $fallback = base_path('vendor/bin/php-cs-fixer');

                if (file_exists($fallback)) {
                    $fallbackCommand = [
                        PHP_BINARY,
                        $fallback,
                        'fix',
                        $targetPath,
                        '--config=' . $configPath,
                        '--allow-risky=yes',
                        '--using-cache=yes',
                        '--allow-unsupported-php-version=yes',
                        '--show-progress=none',
                    ];

                    try {
                        $fallbackProcess = new Process($fallbackCommand);
                        $fallbackProcess->run();
                    } catch (\Throwable $ignored) {
                    }
                }
            }
        } catch (\Throwable $ignored) {
        }
    }





}