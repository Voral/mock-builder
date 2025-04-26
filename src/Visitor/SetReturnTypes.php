<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use Exception;
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

/**
 * PublicMethodVisitor is a custom AST visitor that modifies the Abstract Syntax Tree (AST) of PHP code.
 * Its purpose is to:
 * - Retain only public methods in classes.
 * - Clear the bodies of these public methods (remove their content).
 */
class SetReturnTypes extends ModuleVisitor
{
    private DocBlockFactory|DocBlockFactoryInterface $docBlockFactory;

    public function __construct(
        private readonly string $targetPhpVersion = PHP_VERSION,
        bool $skipThrowable = false,
    ) {
        parent::__construct($skipThrowable);
        $this->docBlockFactory = DocBlockFactory::createInstance();
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
        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return null;
            }
            foreach ($node->stmts as $method) {
                if ($method instanceof ClassMethod) {
                    $this->addReturnType($method);
                }
            }
            $node->stmts = array_values($node->stmts);
        }

        return null;
    }

    private function addReturnType(ClassMethod $method): void
    {
        if ($method->returnType) {
            return;
        }

        $docComment = $method->getDocComment();
        if ($docComment) {
            try {
                $docBlock = $this->docBlockFactory->create($docComment->getText());
                /** @var TagWithType[] $returnTag */
                $returnTag = $docBlock->getTagsByName('return');
                if (!empty($returnTag)) {
                    $type = $returnTag[0]->getType();
                    $typeName = (string) $type;

                    $parts = array_map('trim', explode('|', $typeName));
                    if (count($parts) > 1 && in_array('mixed', $parts, true)) {
                        $typeName = 'mixed';
                    }
                    if ('true' === $typeName && version_compare($this->targetPhpVersion, '8.2.0', '<')) {
                        $typeName = 'bool';
                    }

                    if ('$this' === $typeName) {
                        $method->returnType = new Identifier('static');

                        return;
                    }
                    if ('void' === $typeName) {
                        $method->returnType = new Identifier('void');

                        return;
                    }

                    if (str_starts_with($typeName, '?')) {
                        $innerTypeName = substr($typeName, 1);
                        $method->returnType = new NullableType(new Name($innerTypeName));

                        return;
                    }

                    $method->returnType = new Name($typeName);
                }
            } catch (Exception $e) {
                return;
            }
        }
    }
}
