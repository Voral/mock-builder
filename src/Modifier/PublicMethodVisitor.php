<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Modifier;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;

/**
 * PublicMethodVisitor is a custom AST visitor that modifies the Abstract Syntax Tree (AST) of PHP code.
 * Its purpose is to:
 * - Retain only public methods in classes.
 * - Clear the bodies of these public methods (remove their content).
 */
class PublicMethodVisitor extends NodeVisitorAbstract
{
    /**
     * Called when leaving a node during traversal of the AST.
     *
     * @param mixed $node the current node being processed
     */
    public function leaveNode($node): void
    {
        if ($node instanceof Class_ || $node instanceof Trait_) {
            $node->stmts = array_filter($node->stmts, static fn($stmt) => $stmt instanceof ClassMethod
                && $stmt->isPublic());

            foreach ($node->stmts as $method) {
                if ($method instanceof ClassMethod) {
                    if ($method->isAbstract()) {
                        $method->stmts = null;
                    } else {
                        $method->stmts = [];
                    }
                }
            }
        }
    }
}
