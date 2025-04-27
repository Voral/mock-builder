<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

use Vasoft\MockBuilder\Visitor\ModuleVisitor;

/**
 * The Application class is the main entry point for the mock builder utility.
 * It is responsible for:
 * - Parsing command-line arguments and configuration files.
 * - Initializing the processing pipeline.
 * - Coordinating the processing of files or directories.
 *
 * This class acts as a controller that orchestrates the interaction between the user input,
 * configuration, and the core functionality provided by the `Builder` class.
 */
class Application
{
    /**
     * Indicates whether the help message should be displayed.
     */
    private bool $isHelp = false;

    /**
     * @var ModuleVisitor[]
     */
    private array $visitors = [];
    private bool $forceUpdate = false;
    /**
     * @var mixed|string
     */
    private mixed $cachePath = './.vs-mock-builder.cache';

    /**
     * Executes the application logic.
     *
     * This method performs the following steps:
     * 1. Loads configuration from a file (if available).
     * 2. Parses command-line arguments.
     * 3. Validates and prepares paths for processing.
     * 4. Delegates the processing of files or directories to the `Builder` class.
     *
     * If the `--help` option is provided, the help message is displayed instead.
     */
    public function run(): void
    {
        $config = $this->parseArguments($this->loadConfiguration());
        if ($this->isHelp) {
            $this->printHelp();

            return;
        }
        $config = $this->preparePaths($config);

        (new Builder($config, $this->visitors, $this->forceUpdate, $this->cachePath))
            ->run();
    }

    private function printHelp(): void
    {
        echo <<<'HELP'
            Usage: php vs-mock-builder.php [options]

            Options:
              -b, --base <path>     Specify the base path for the source files. Default is the current working directory.
              -t, --target <path>   Specify the target path for the generated mocks. Default is the current working directory.
              -f, --filter <filter> Specify a comma-separated list of class names to filter.
              -d, --display         Whether to display the progress during processing.
              -c, --clear-cache     Clear the cache before processing.
              -h, --help            Display this help message and exit.

            HELP;
    }

    private function preparePaths(Config $config): Config
    {
        if (!is_dir($this->cachePath) && !mkdir($this->cachePath, 0o775, true)) {
            echo "Error: Failed to create target directory: {$this->cachePath}\n";
        }

        if (empty($config->basePath) || empty($config->basePath[0])) {
            exit("Error: Base path must be specified.\n");
        }
        foreach ($config->basePath as $basePath) {
            if (!is_readable($basePath)) {
                exit("Error: Base path is not readable: {$basePath}\n");
            }
        }
        $newTargetPath = ('' === $config->targetPath) ? __DIR__ . '/target/' : rtrim($config->targetPath, '/') . '/';
        if (!is_writable(dirname($config->targetPath))) {
            exit("Error: Target directory is not writable: {$config->targetPath}\n");
        }
        $modified = $newTargetPath !== $config->targetPath;

        return $modified ? new Config(targetPath: $newTargetPath) : $config;
    }

    private function parseArguments(Config $config): Config
    {
        $options = getopt('b:t:f:hc', ['base:', 'target:', 'filter:', 'help', 'clear-cache']);
        if (isset($options['h']) || isset($options['help'])) {
            $this->isHelp = true;

            return $config;
        }
        if (isset($options['c']) || isset($options['clear-cache'])) {
            $this->forceUpdate = true;
        }
        $basePath = trim($options['b'] ?? $options['base'] ?? '');
        $targetPath = trim($options['t'] ?? $options['target'] ?? '');
        $filter = trim($options['f'] ?? $options['filter'] ?? '', " \t\n\r\0\x0B,");

        return new Config(
            '' !== $targetPath ? $targetPath : $config->targetPath,
            '' !== $basePath ? [$basePath] : $config->basePath,
            ('' !== $filter) ? explode(',', $filter) : $config->classNameFilter,
        );
    }

    private function loadConfiguration(): Config
    {
        $configFile = getcwd() . '/.vs-mock-builder.php';

        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!is_array($config)) {
                exit("Error: Invalid configuration file format: {$configFile}\n");
            }
            $this->cachePath = rtrim($config['cachePath'] ?? './.mock-builder-cache', '/') . '/';
            if (isset($config['visitors']) && is_array($config['visitors'])) {
                $this->visitors = array_reverse($config['visitors']);
            }
            $config['basePath'] ??= [];
            if (!is_array($config['basePath'])) {
                $config['basePath'] = [$config['basePath']];
            }
            $configTest = $config;
            unset($configTest['visitors']);

            return new Config(
                $config['targetPath'] ?? '',
                $config['basePath'],
                $config['classNameFilter'] ?? [],
            );
        }

        return new Config();
    }
}
