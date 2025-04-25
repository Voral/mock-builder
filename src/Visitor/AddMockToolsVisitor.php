<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\NodeVisitorAbstract;

class AddMockToolsVisitor extends NodeVisitorAbstract
{
    public function leaveNode($node)
    {
        if ($node instanceof Class_ || $node instanceof Trait_) {
            // Инициализируем stmts, если они отсутствуют
            if (!isset($node->stmts)) {
                $node->stmts = [];
            }

            // Добавляем трейт в начало
            array_unshift(
                $node->stmts,
                new TraitUse([
                    new Name('\Vasoft\MockBuilder\Mocker\MockTools'),
                ]),
            );
        }

        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return;
            }
            foreach ($node->stmts as $method) {
                if ($method instanceof ClassMethod) {
                    $result = $this->processMethodNode($method);
                    if (null !== $result) {
                        $method->stmts = $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @return null|Node\Stmt[]
     */
    private function processMethodNode(ClassMethod $method): ?array
    {
        if ($method->isAbstract()) {
            return null;
        }

        return $this->processMethod($method);
    }

    /**
     * @return Node\Stmt[]
     */
    private function processMethod(ClassMethod $method): array
    {
        $params = array_map(
            static fn($param) => new Node\Expr\ArrayItem(new Node\Expr\Variable($param->var->name)),
            $method->getParams(),
        );
        $staticCall = new Node\Expr\StaticCall(
            new Name('self'),
            'executeMocked',
            [
                new Node\Scalar\String_($method->name->toString()),
                new Node\Expr\Array_($params),
            ],
        );
        $returnsValue = $this->shouldReturn($method);
        if ($returnsValue) {
            return [new Node\Stmt\Return_($staticCall)];
        }

        return [new Node\Stmt\Expression($staticCall)];
    }

    private function shouldReturn(ClassMethod $method): bool
    {
        $returnType = $this->getReturnTypeAsString($method);

        return $returnType && 'void' !== $returnType;
    }

    private function getReturnTypeAsString(ClassMethod $method): ?string
    {
        $returnType = $method->getReturnType();

        if (!$returnType) {
            return null;
        }

        if ($returnType instanceof Name || $returnType instanceof Identifier) {
            return $returnType->toString();
        }

        if ($returnType instanceof NullableType) {
            return '?' . $returnType->type->toString();
        }

        return null;
    }
}
