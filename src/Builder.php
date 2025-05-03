<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
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
    private array $processedFiles = [];
    private Graph $graph;
    private NodeTraverser $traverser;

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
    ) {
        $cacheFile = $this->cachePath . md5(serialize($this->config->basePath)) . '.graph';
        $this->graph = new Graph($this->config->basePath, $cacheFile, $this->forceUpdate);
        $this->traverser = new NodeTraverser();
        if (!empty($this->visitors)) {
            foreach ($this->visitors as $visitor) {
                $visitor
                    ->setDependenceGraph($this->graph)
                    ->setConfig($this->config)
                    ->beforeProcess();
                $this->traverser->addVisitor($visitor);
            }
        }
        $this->traverser->addVisitor(new NameResolver());
    }

    public function run(): void
    {
        $this->processedFiles = [];
        $this->graph->traverse($this->processFile(...));
    }

    /**
     * Processes a single PHP file: parses it, modifies the AST, and saves the result.
     *
     * @param string $filePath the path to the PHP file to process
     */
    protected function processFile(string $filePath): void
    {
        if (array_key_exists($filePath, $this->processedFiles)) {
            return;
        }
        $this->processedFiles[$filePath] = true;

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

        // Step 1: Collect all use statements from the global scope or namespaces
        $globalUseStatements = [];
        $namespaceUseStatements = [];
        foreach ($ast as $node) {
            if ($node instanceof Use_) {
                // Collect use statements in the global scope
                foreach ($node->uses as $use) {
                    $globalUseStatements[] = new Use_(
                        [new UseUse(new Name($use->name->toString()))],
                    );
                }
            } elseif ($node instanceof Namespace_) {
                // Collect use statements inside namespaces
                $namespaceName = $node->name ? $node->name->toString() : null;
                foreach ($node->stmts as $stmt) {
                    if ($stmt instanceof Use_) {
                        foreach ($stmt->uses as $use) {
                            $namespaceUseStatements[$namespaceName][] = new Use_(
                                [new UseUse(new Name($use->name->toString()))],
                            );
                        }
                    }
                }
            }
        }

        $modifiedAst = $this->traverser->traverse($ast);

        $printer = new PrettyPrinter\Standard();

        foreach ($modifiedAst as $node) {
            if ($node instanceof Namespace_) {
                // Check if namespace name exists
                $namespaceName = $node->name ? $node->name->toString() : null;

                // Add collected use statements to the namespace
                if ($namespaceName && !empty($namespaceUseStatements[$namespaceName])) {
                    $node->stmts = array_merge($namespaceUseStatements[$namespaceName], [new Nop()], $node->stmts);
                }

                $namespace = $node->name ? implode('\\', $node->name->getParts()) : '';

                foreach ($node->stmts as $stmt) {
                    if ($this->neededNode($stmt)) {
                        if (!$this->matchesFilter($stmt)) {
                            continue;
                        }

                        // Create a new Namespace node with only the current class
                        $newNamespace = new Namespace_(
                            $node->name,
                            [],
                        );

                        // Add collected use statements to the new namespace
                        if ($namespaceName && !empty($namespaceUseStatements[$namespaceName])) {
                            $newNamespace->stmts = array_merge(
                                $namespaceUseStatements[$namespaceName],
                                [new Nop()],
                            );
                        }

                        // Add the current class to the new namespace
                        $newNamespace->stmts[] = $stmt;

                        $classCode = $printer->prettyPrint([$newNamespace]);
                        $this->saveClassToFile($stmt, $namespace, '<?php' . PHP_EOL . $classCode, $filePath);
                    }
                }
            } elseif ($this->neededNode($node)) {
                if (!$this->matchesFilter($node)) {
                    continue;
                }

                // Add global use statements for classes in the global scope
                $classCode = $printer->prettyPrint(
                    array_merge(
                        $globalUseStatements ?: [],
                        [new Nop()],
                        [$node],
                    ),
                );
                $this->saveClassToFile($node, '', '<?php' . PHP_EOL . $classCode, $filePath);
            }
        }
    }

    private function saveClassToFile(
        \PhpParser\Node $node,
        string $namespace,
        string $classCode,
        string $filePath,
    ): void {
        $class = $node->name->name ?? '';

        if (empty($class)) {
            echo "Warning: Class name not found in the file: {$filePath}\n";

            return;
        }

        if ($this->config->displayProgress && empty($namespace)) {
            echo "Info: Class '{$class}' is in the global namespace. File: {$filePath}\n";
        }

        $targetDir = $this->config->targetPath . str_replace('\\', '/', $namespace);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0o775, true)) {
            exit("Error: Failed to create target directory: {$targetDir}\n");
        }

        $targetFile = $targetDir . '/' . $class . '.php';
        if (false === file_put_contents($targetFile, $classCode)) {
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
