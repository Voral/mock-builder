<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use Vasoft\MockBuilder\Informer\EntityData;
use Vasoft\MockBuilder\Informer\EntityType;

final class Graph
{
    private array $classes = [];

    public function __construct(
        private readonly array $basePaths,
        private readonly string $cacheFile,
        private readonly bool $forceUpdate,
    ) {
        if ('' !== $cacheFile && !$this->forceUpdate && file_exists($this->cacheFile)) {
            $this->classes = unserialize(
                file_get_contents($this->cacheFile),
                ['allowed_classes' => [EntityData::class, EntityType::class]],
            );

            return;
        }
        $this->classes = [];
        foreach ($this->basePaths as $basePath) {
            $this->collectClassInfo($basePath);
        }
        $this->buildDependencyGraph();
        if ('' !== $cacheFile) {
            file_put_contents($this->cacheFile, serialize($this->classes));
        }
    }

    public function isInstanceOf(string $itemName, array $classNames): bool
    {
        $itemName = ltrim($itemName, '\\');
        if (!isset($this->classes[$itemName])) {
            return false;
        }
        $info = $this->classes[$itemName];
        $classNames = array_map(static fn($className) => ltrim($className, '\\'), $classNames);

        foreach ($info->interfaces as $interface) {
            if (in_array($interface, $classNames, true)) {
                return true;
            }
        }

        foreach ($info->parents as $parentClassName) {
            if (in_array($parentClassName, $classNames, true) || $this->isInstanceOf($parentClassName, $classNames)) {
                return true;
            }
        }

        return false;
    }

    public function buildDependencyGraph(): void
    {
        foreach ($this->classes as $className => $info) {
            foreach ($info->parents as $parentClassName) {
                if (isset($this->classes[$parentClassName])) {
                    $this->classes[$parentClassName]->children[] = $className;
                }
            }
        }
    }

    private function collectClassInfo(string $basePath): void
    {
        $parser = (new ParserFactory())->createForHostVersion();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($basePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $file) {
            if ($file->isFile() && 'php' === strtolower($file->getExtension())) {
                $code = file_get_contents($file->getPathname());

                try {
                    $ast = $parser->parse($code);
                    $traverser = new NodeTraverser();
                    $traverser->addVisitor(new NameResolver());

                    $modifiedAst = $traverser->traverse($ast);
                } catch (\Exception $e) {
                    echo "Error parsing file {$file->getPathname()}: " . $e->getMessage() . "\n";

                    continue;
                }
                $this->prepareClasses($modifiedAst, $file->getPathname());
            }
        }
    }

    private function prepareClasses(array $ast, string $fileName): void
    {
        foreach ($ast as $node) {
            if ($node instanceof Namespace_) {
                if (!empty($node->stmts)) {
                    $this->prepareClasses($node->stmts, $fileName);
                }

                continue;
            }
            $this->prepareClass($node, $fileName)
            || $this->prepareInterface($node, $fileName)
            || $this->prepareTrait($node, $fileName);
        }
    }

    private function prepareInterface($node, string $fileName): bool
    {
        if ($node instanceof Interface_) {
            $interfaceName = $node->namespacedName?->toString() ?? $node->name->toString();
            $parentInterfaces = array_map(static fn($interface) => $interface->toString(), $node->extends);

            $this->classes[$interfaceName] = new EntityData(
                EntityType::IS_INTERFACE,
                $interfaceName,
                $fileName,
                parents: $parentInterfaces,
            );

            return true;
        }

        return false;
    }

    private function prepareClass($node, string $fileName): bool
    {
        if ($node instanceof Class_) {
            $className = $node->namespacedName?->toString() ?? $node->name->toString();
            $parentClass = $node->extends?->toString();
            $interfaces = array_map(static fn($interface) => $interface->toString(), $node->implements);

            $this->classes[$className] = new EntityData(
                EntityType::IS_CLASS,
                $className,
                $fileName,
                $interfaces,
                empty($parentClass) ? [] : [$parentClass],
            );

            return true;
        }

        return false;
    }

    private function prepareTrait($node, string $fileName): bool
    {
        if ($node instanceof Trait_) {
            $name = $node->namespacedName?->toString() ?? $node->name->toString();

            $this->classes[$name] = new EntityData(EntityType::IS_TRAIT, $name, $fileName);

            return true;
        }

        return false;
    }

    private function topologicalSort(): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function ($className) use (&$visit, &$sorted, &$visited, &$visiting): void {
            if (isset($visited[$className])) {
                return;
            }
            if (isset($visiting[$className])) {
                throw new \LogicException("Circular dependency detected involving {$className}");
            }

            $visiting[$className] = true;

            foreach ($this->classes[$className]->parents as $parent) {
                if (isset($this->classes[$parent])) {
                    $visit($parent);
                }
            }

            unset($visiting[$className]);
            $visited[$className] = true;
            $sorted[] = $className;
        };

        foreach ($this->classes as $className => $info) {
            if (EntityType::IS_INTERFACE === $info->type) {
                $visit($className);
            }
        }

        foreach ($this->classes as $className => $info) {
            if (EntityType::IS_TRAIT === $info->type) {
                $visit($className);
            }
        }

        foreach ($this->classes as $className => $info) {
            if (EntityType::IS_CLASS === $info->type) {
                $visit($className);
            }
        }

        return $sorted;
    }

    public function traverse(callable $callback): void
    {
        $sorted = $this->topologicalSort();
        foreach ($sorted as $className) {
            if (isset($this->classes[$className])) {
                $callback($this->classes[$className]->filePath);
            }
        }
    }
}
