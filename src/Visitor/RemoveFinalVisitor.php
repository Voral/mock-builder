<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Modifiers;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * RemoveFinalVisitor is a AST visitor that removes the `final` modifier from classes and methods.
 */
class RemoveFinalVisitor extends ModuleVisitor
{
    /**
     * Called when entering a node during traversal of the AST.
     *
     * @param Node $node the current node being processed
     *
     * @return null|int|Node|Node[]
     */
    public function enterNode(Node $node): null|array|int|Node
    {
        // Убираем `final` у класса
        if ($node instanceof Class_ && $node->isFinal()) {
            $node->flags &= ~Modifiers::FINAL; // Сбрасываем флаг `final`
        }

        // Убираем `final` у метода
        if ($node instanceof ClassMethod && $node->isFinal()) {
            $node->flags &= ~Modifiers::FINAL; // Сбрасываем флаг `final`
        }

        return $node;
    }
}
