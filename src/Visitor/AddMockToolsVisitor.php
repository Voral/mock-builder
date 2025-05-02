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

class AddMockToolsVisitor extends ModuleVisitor
{
    private string $baseNamespace;
    private string $traitName;

    public function __construct(string $baseNamespace, bool $skipThrowable = false)
    {
        parent::__construct($skipThrowable);
        $this->baseNamespace = trim($baseNamespace, '\\');
        $this->traitName = '\\' . $baseNamespace . '\Mocker\MockTools';
    }

    public function beforeProcess(): void
    {
        if (null === $this->config) {
            return;
        }

        $traitTargetDir = $this->config->targetPath . '/' . str_replace('\\', '/', $this->baseNamespace) . '/Mocker';

        if (!is_dir($traitTargetDir)) {
            mkdir($traitTargetDir, 0o775, true);
        }
        $this->copyMockTools($traitTargetDir . '/MockTools.php');
        $this->copyMockTools($traitTargetDir . '/MockDefinition.php');
    }

    private function copyMockTools(string $targetFile): void
    {
        if (!file_exists($targetFile)) {
            $content = file_get_contents(__DIR__ . '/../Mocker/MockTools.php');
            $content = str_replace(
                'namespace Vasoft\MockBuilder\Mocker;',
                'namespace ' . $this->baseNamespace . '\Mocker;',
                $content,
            );
            file_put_contents($targetFile, $content);
        }
    }

    /**
     * @param mixed $node
     *
     * @return null|int|Node|Node[]
     */
    public function leaveNode($node): null|array|int|Node
    {
        if ($this->needSkip($node)) {
            return null;
        }
        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                $node->stmts = [];
            }

            array_unshift($node->stmts, new TraitUse([new Name($this->traitName)]));
        }

        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return null;
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
                new Node\Arg(new Node\Scalar\String_($method->name->toString())),
                new Node\Arg(new Node\Expr\Array_($params)),
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
