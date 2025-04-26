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
    private readonly string $targetPath;

    /**
     * Constructor for the Builder class.
     *
     * @param string          $targetPath      The target directory for saving transformed files.
     *                                         If empty, defaults to './target/'.
     * @param string[]        $classNameFilter Optional list of substrings to filter class names.
     *                                         Only classes whose names contain at least one of these substrings will be processed.
     * @param ModuleVisitor[] $visitors
     */
    public function __construct(
        private readonly string $basePath,
        string $targetPath,
        private readonly array $classNameFilter,
        private readonly array $visitors = [],
        private readonly bool $forceUpdate = false,
        private readonly string $cacheFile = '',
    ) {
        $this->targetPath = '' === $targetPath ? __DIR__ . '/target/' : rtrim($targetPath, '/') . '/';
    }

    /**
     * Processes all PHP files in a directory recursively.
     *
     * @param string $dirPath the path to the directory containing PHP files to process
     */
    public function processDirectory(string $dirPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dirPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === strtolower($file->getExtension())) {
                $filePath = $file->getPathname();
                $this->processFile($filePath);
            }
        }
    }

    /**
     * Processes a single PHP file: parses it, modifies the AST, and saves the result.
     *
     * @param string $filePath the path to the PHP file to process
     */
    public function processFile(string $filePath): void
    {
        echo "Processing file {$filePath}\n";
        $code = file_get_contents($filePath);

        $parser = (new ParserFactory())->createForHostVersion();

        try {
            $ast = $parser->parse($code);
        } catch (\Exception $e) {
            echo "Error parsing file {$filePath}: " . $e->getMessage() . "\n";

            return;
        }

        $traverser = new NodeTraverser();
        if (!empty($this->visitors)) {
            $graph = new Graph($this->basePath, $this->cacheFile, $this->forceUpdate);
            foreach ($this->visitors as $visitor) {
                $visitor->setDependenceGraph($graph);
                $traverser->addVisitor($visitor);
            }
        }
        $traverser->addVisitor(new NameResolver());
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
                    echo "Unexpected namespace format in file {$filePath}: " . print_r($node->name, true) . "\n";

                    return;
                }
                foreach ($node->stmts as $stmt) {
                    if (isset($stmt->name->name) && $this->neededNode($stmt)) {
                        if (!$this->matchesFilter($stmt->name->name)) {
                            return;
                        }
                        $class = $stmt->name->name;
                        break;
                    }
                }
            } elseif (isset($node->name->name) && $this->neededNode($node)) {
                if (!$this->matchesFilter($node->name->name)) {
                    return;
                }
                $class = $node->name->name;
                break;
            }
        }

        if (empty($class)) {
            echo "Class name not found in the file: {$filePath}\n";

            return;
        }

        if (empty($namespace)) {
            echo "Namespace name not found in the file: {$filePath}\n";

            return;
        }

        $targetDir = $this->targetPath . str_replace('\\', '/', $namespace);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0o775, true)) {
            echo "Failed to create target directory: {$targetDir}\n";

            return;
        }
        $targetFile = $targetDir . '/' . $class . '.php';
        if (false === file_put_contents($targetFile, $newCode)) {
            echo "Failed to save transformed file: {$targetFile}\n";
        }
    }

    private function neededNode(mixed $item): bool
    {
        return $item instanceof Class_ || $item instanceof Interface_ || $item instanceof Trait_;
    }

    private function matchesFilter(string $className): bool
    {
        if (empty($this->classNameFilter)) {
            return true;
        }
        foreach ($this->classNameFilter as $filter) {
            if (false !== stripos($className, $filter)) {
                return true;
            }
        }

        return false;
    }
}
