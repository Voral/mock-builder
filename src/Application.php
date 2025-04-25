<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

use PhpParser\NodeVisitorAbstract;

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
     * Path to the input file to be processed.
     */
    private string $filePath = '';
    /**
     * Path to the directory containing files to be processed.
     */
    private string $directoryPath = '';
    /**
     * Base path for resolving relative paths.
     */
    private string $basePath = '';
    /**
     * Target path where the transformed files will be saved.
     */
    private string $targetPath = '';
    /**
     * List of class name filters to include specific classes during processing.
     *
     * @var string[]
     */
    private array $classNameFilter = [];
    /**
     * @var NodeVisitorAbstract[]
     */
    private array $visitors = [];

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
        $this->loadConfiguration();
        $this->parseArguments();
        if ($this->isHelp) {
            $this->printHelp();

            return;
        }
        $this->preparePaths();

        $builder = new Builder(
            $this->targetPath,
            $this->classNameFilter,
            $this->visitors,
        );
        if ('' !== $this->filePath) {
            $builder->processFile($this->filePath);
        }
        if ('' !== $this->directoryPath) {
            $builder->processDirectory($this->directoryPath);
        }
    }

    private function printHelp(): void
    {
        echo <<<'HELP'
            Usage: php vs-mock-builder.php [options]

            Options:
              -i, --file <path>     Specify the path to a PHP file to be processed.
              -d, --dir <path>      Specify the path to a directory containing PHP files to be processed.
              -b, --base <path>     Specify the base path for the source files. Default is the current working directory.
              -t, --target <path>   Specify the target path for the generated mocks. Default is the current working directory.
              -f, --filter <filter> Specify a comma-separated list of class names to filter.
              -h, --help            Display this help message and exit.

            HELP;
    }

    private function preparePaths(): void
    {
        if ('' === $this->filePath && '' === $this->directoryPath) {
            exit("Error: Either a file or a directory must be specified.\n");
        }
        if ('' !== $this->filePath) {
            $this->filePath = rtrim($this->basePath, '/') . '/' . $this->filePath;
            if (!file_exists($this->filePath)) {
                exit("File not found: {$this->filePath}\n");
            }
            if (!is_readable($this->filePath)) {
                exit("File is not readable: {$this->filePath}\n");
            }
        }
        if ('' !== $this->directoryPath) {
            if (!is_readable($this->directoryPath)) {
                exit("Directory is not readable: {$this->directoryPath}\n");
            }
            $this->directoryPath = rtrim($this->basePath, '/') . '/' . $this->directoryPath;
        }
        $this->targetPath = ('' === $this->targetPath) ? __DIR__ . '/target/' : rtrim($this->targetPath, '/') . '/';
        if (!is_writable(dirname($this->targetPath))) {
            exit("Target directory is not writable: {$this->targetPath}\n");
        }
    }

    private function parseArguments(): void
    {
        $options = getopt('i:d:b:t:f:h', ['file:', 'dir:', 'base:', 'target:', 'filter:', 'help']);
        if (isset($options['h']) || isset($options['help'])) {
            $this->isHelp = true;

            return;
        }
        $filePath = trim($options['i'] ?? $options['file'] ?? '');
        if ('' !== $filePath) {
            $this->filePath = $filePath;
        }
        $directoryPath = trim($options['d'] ?? $options['dir'] ?? '');
        if ('' !== $directoryPath) {
            $this->directoryPath = $directoryPath;
        }
        $basePath = trim($options['b'] ?? $options['base'] ?? '');
        if ('' !== $basePath) {
            $this->basePath = $basePath;
        }
        $targetPath = trim($options['t'] ?? $options['target'] ?? '');
        if ('' !== $targetPath) {
            $this->targetPath = $targetPath;
        }
        $filter = trim($options['f'] ?? $options['filter'] ?? '', " \t\n\r\0\x0B,");
        if ('' !== $filter) {
            $this->classNameFilter = explode(',', $filter);
        }
    }

    private function loadConfiguration(): void
    {
        $configFile = getcwd() . '/.vs-mock-builder.php';

        if (file_exists($configFile)) {
            $config = include $configFile;
            if (!is_array($config)) {
                exit("Invalid configuration file format: {$configFile}\n");
            }
            $this->filePath = $config['filePath'] ?? '';
            $this->directoryPath = $config['directoryPath'] ?? '';
            $this->basePath = $config['basePath'] ?? '';
            $this->targetPath = $config['targetPath'] ?? '';
            $this->classNameFilter = $config['classNameFilter'] ?? [];
            if (isset($config['visitors']) && is_array($config['visitors'])) {
                $this->visitors = array_reverse($config['visitors']);
            }
        }
    }
}
