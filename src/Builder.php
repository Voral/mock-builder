<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PHPStan\PhpDocParser\Ast\Node;
use Vasoft\MockBuilder\Visitor\ModuleVisitor;

/**
 * The Builder class is responsible for processing PHP files and directories to generate mock classes.
 * It performs the following tasks:
 * - Parses PHP files into an Abstract Syntax Tree (AST).
 * - Modifies the AST to retain only public methods and clear their bodies.
 * - Saves the transformed code into target directories while preserving the PSR-4 structure.
 *
 * This class is designed to work with both individual files and entire directories.
 */
class Builder
{
    private ?Graph $graph = null;

    /**
     * Constructor for the Builder class.
     *
     * @param ModuleVisitor[] $visitors
     */
    public function __construct(
        private readonly Config $config,
        private readonly array $visitors = [],
        private readonly bool $forceUpdate = false,
        private readonly string $cachePath = '',
    ) {}

    public function run(): void
    {
        $cacheFile = $this->cachePath . md5(serialize($this->config->basePath)) . '.graph';

        $this->graph = new Graph($this->config->basePath, $cacheFile, $this->forceUpdate);
        foreach ($this->config->basePath as $basePath) {
            $this->processDirectory($basePath);
        }
    }

    /**
     * Processes all PHP files in a directory recursively.
     *
     * @param string $dirPath the path to the directory containing PHP files to process
     */
    protected function processDirectory(string $dirPath): void
    {
        if ($this->config->displayProgress) {
            echo 'Processing directory: ', $dirPath, PHP_EOL;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        $traverser = new NodeTraverser();
        if (!empty($this->visitors)) {
            foreach ($this->visitors as $visitor) {
                $visitor
                    ->setDependenceGraph($this->graph)
                    ->setConfig($this->config)
                    ->beforeProcess();
                $traverser->addVisitor($visitor);
            }
        }
        $traverser->addVisitor(new NameResolver());

        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === strtolower($file->getExtension())) {
                $filePath = $file->getPathname();
                $this->processFile($filePath, $traverser);
            }
        }
    }

    /**
     * Processes a single PHP file: parses it, modifies the AST, and saves the result.
     *
     * @param string $filePath the path to the PHP file to process
     */
    protected function processFile(string $filePath, NodeTraverser $traverser): void
    {
        if ($this->config->displayProgress) {
            echo 'Processing file: ', $filePath, PHP_EOL;
        }
        $code = file_get_contents($filePath);

        $parser = (new ParserFactory())->createForHostVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\Exception $e) {
            echo 'Warning: Error parsing file ', $filePath, ': ' . $e->getMessage() . PHP_EOL;

            return;
        }

        $modifiedAst = $traverser->traverse($ast);

        $printer = new PrettyPrinter\Standard();
        $newCode = $printer->prettyPrintFile($modifiedAst);

        $namespace = '';
        $class = '';
        foreach ($modifiedAst as $node) {
            if ($node instanceof Namespace_) {
                if ($node->name instanceof Name) {
                    $namespace = implode('\\', $node->name->getParts());
                } else {
                    echo "Warning: Unexpected namespace format in file {$filePath}: " . print_r(
                        $node->name,
                        true,
                    ) . "\n";

                    return;
                }
                foreach ($node->stmts as $stmt) {
                    if (isset($stmt->name->name) && $this->neededNode($stmt)) {
                        if (!$this->matchesFilter($stmt)) {
                            return;
                        }
                        $class = $stmt->name->name;
                        break;
                    }
                }
            } elseif (isset($node->name->name) && $this->neededNode($node)) {
                if (!$this->matchesFilter($node)) {
                    return;
                }
                $class = $node->name->name;
                break;
            }
        }

        if (empty($class)) {
            echo "Warning: Class name not found in the file: {$filePath}\n";

            return;
        }

        if (empty($namespace)) {
            echo "Warning: Namespace name not found in the file: {$filePath}\n";

            return;
        }

        $targetDir = $this->config->targetPath . str_replace('\\', '/', $namespace);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0o775, true)) {
            exit("Error: Failed to create target directory: {$targetDir}\n");
        }
        $targetFile = $targetDir . '/' . $class . '.php';
        if (false === file_put_contents($targetFile, $newCode)) {
            exit("Error: Failed to save transformed file: {$targetFile}\n");
        }
    }

    private function neededNode(mixed $item): bool
    {
        return $item instanceof Class_ || $item instanceof Interface_ || $item instanceof Trait_;
    }

    private function matchesFilter(Node|\PhpParser\Node $node): bool
    {
        $className = $node->name->name ?? '';
        if (isset($node->namespacedName)) {
            $className = $node->namespacedName->toString();
        }
        if (empty($this->config->classNameFilter)) {
            return true;
        }
        foreach ($this->config->classNameFilter as $filter) {
            if (false !== stripos($className, $filter)) {
                return true;
            }
        }

        return false;
    }
}
