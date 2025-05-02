<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use phpDocumentor\Reflection\DocBlock\Tags\TagWithType;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;

/**
 * PublicMethodVisitor is a custom AST visitor that modifies the Abstract Syntax Tree (AST) of PHP code.
 * Its purpose is to:
 * - Retain only public methods in classes.
 * - Clear the bodies of these public methods (remove their content).
 */
class SetReturnTypes extends ModuleVisitor
{
    private DocBlockFactory|DocBlockFactoryInterface $docBlockFactory;
    private array $imports = [];

    public function __construct(
        private readonly string $targetPhpVersion = PHP_VERSION,
        bool $skipThrowable = false,
    ) {
        parent::__construct($skipThrowable);
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    public function enterNode(Node $node): null|array|int|Node
    {
        if ($node instanceof Use_) {
            foreach ($node->uses as $use) {
                $this->addImport($use);
            }
        }

        return $node;
    }

    private function addImport(UseUse $use): void
    {
        $alias = $use->alias ? $use->alias->name : $use->name->getLast();
        $this->imports[$alias] = $use->name->toString();
    }

    private function getImports(): array
    {
        return $this->imports;
    }

    /**
     * Called when leaving a node during traversal of the AST.
     *
     * @param Node $node the current node being processed
     *
     * @return null|int|Node|Node[]
     */
    public function leaveNode(Node $node): null|array|int|Node
    {
        $className = $node->name->name ?? '';
        if (isset($node->namespacedName)) {
            $className = $node->namespacedName->toString();
        }
        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return null;
            }

            $data = $this->dependenceGraph->getEntityData($className);

            foreach ($node->stmts as $method) {
                if ($method instanceof ClassMethod) {
                    $methodName = $className . '::' . $method->name->name;
                    if (!$method->returnType) {
                        if (!empty($this->config->resultTypes) && !empty($this->config->resultTypes[$methodName])) {
                            $method->returnType = new Identifier($this->config->resultTypes[$methodName]);
                        } elseif (null !== $data) {
                            $method->returnType = $data->getMethodReturnTypeRecursively(
                                $method->name->name,
                                $this->dependenceGraph,
                            );
                        }
                    }
                    if (!$method->returnType) {
                        $this->addReturnType($method, $className);
                    }
                    $data?->setMethodReturnType($method->name->name, $method->returnType);
                }
            }
            $node->stmts = array_values($node->stmts);
        }

        return null;
    }

    private function addReturnType(ClassMethod $method, string $className): void
    {
        $docComment = $method->getDocComment();
        if ($docComment) {
            try {
                $docBlock = $this->docBlockFactory->create($docComment->getText());
                /** @var TagWithType[] $returnTag */
                $returnTag = $docBlock->getTagsByName('return');
                if (!empty($returnTag)) {
                    $type = $returnTag[0]->getType();
                    $typeName = trim((string) $type);

                    $parts = array_map('trim', explode('|', $typeName));
                    $parts = array_map([$this, 'resolveTypeName'], $parts);
                    if (count($parts) > 1 && in_array('mixed', $parts, true)) {
                        $typeName = 'mixed';
                    } else {
                        foreach ($parts as &$part) {
                            $part = trim($part);
                            if ('callback' === $part) {
                                $part = 'callable';
                            } elseif (str_ends_with($part, '[]')) {
                                $part = 'array';
                            }
                        }
                        $parts = array_unique($parts);
                        $typeName = implode('|', $parts);
                        if (count($parts) > 1) {
                            $method->returnType = new Identifier($typeName);

                            return;
                        }
                    }

                    if ('true' === $typeName && version_compare($this->targetPhpVersion, '8.2.0', '<')) {
                        $typeName = 'bool';
                    }
                    if ('$this' === $typeName) {
                        $method->returnType = new Identifier('static');

                        return;
                    }
                    if ('void' === $typeName || 'null' === $typeName) {
                        $method->returnType = new Identifier('void');

                        return;
                    }
                    if ('resource' === $typeName) {
                        $method->returnType = new Identifier('mixed');

                        return;
                    }
                    if (str_starts_with($typeName, '?')) {
                        $innerTypeName = substr($typeName, 1);
                        $method->returnType = new NullableType(
                            new Name($this->resolveTypeName($innerTypeName)),
                        );

                        return;
                    }

                    $method->returnType = new Name($this->resolveTypeName($typeName));
                }
            } catch (\Exception $e) {
                return;
            }
        }
    }

    private function resolveTypeName(string $typeName): string
    {
        if (str_starts_with($typeName, '\\')) {
            $typeNameWithoutSlash = substr($typeName, 1);

            $imports = $this->getImports();

            foreach ($imports as $alias => $fullyQualifiedClassName) {
                if ($alias === $typeNameWithoutSlash || $fullyQualifiedClassName === $typeNameWithoutSlash) {
                    return $typeNameWithoutSlash;
                }
            }

            $currentNamespace = $this->getCurrentNamespace();
            if ($currentNamespace && str_starts_with($typeNameWithoutSlash, $currentNamespace)) {
                return $typeNameWithoutSlash;
            }

            return $typeName;
        }

        return $typeName;
    }

    private function getCurrentNamespace(): ?string
    {
        return $this->currentNamespace ?? null;
    }
}
