<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Node;

class PublicAndConstFilter extends NodeVisitorAbstract
{
    public function leaveNode($node): void
    {
        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return;
            }
            $node->stmts = array_filter(
                $node->stmts,
                static fn($stmt) => ($stmt instanceof ClassMethod && $stmt->isPublic())
                    || ($stmt instanceof Node\Stmt\ClassConst),
            );
        }
    }
}
