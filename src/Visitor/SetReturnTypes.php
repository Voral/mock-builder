<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use phpDocumentor\Reflection\DocBlockFactory;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
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
class SetReturnTypes extends NodeVisitorAbstract
{
    private DocBlockFactory $docBlockFactory;

    public function __construct(
        private readonly string $targetPhpVersion = PHP_VERSION,
    ) {
        $this->docBlockFactory = DocBlockFactory::createInstance();
    }

    /**
     * Called when leaving a node during traversal of the AST.
     *
     * @param Class_|Trait_ $node the current node being processed
     */
    public function leaveNode($node): void
    {
        if ($node instanceof Class_ || $node instanceof Trait_) {
            if (!isset($node->stmts)) {
                return;
            }
            foreach ($node->stmts as $key => $method) {
                if ($method instanceof ClassMethod) {
                    $this->addReturnType($method);
                }
            }
            $node->stmts = array_values($node->stmts);
        }
    }

    private function addReturnType(ClassMethod $method): void
    {
        // Если тип уже указан, ничего не делаем
        if ($method->returnType) {
            return;
        }

        // Получаем тип из PHPDoc
        $docComment = $method->getDocComment();
        if ($docComment) {
            try {
                $docBlock = $this->docBlockFactory->create($docComment->getText());
                $returnTag = $docBlock->getTagsByName('return');
                if (!empty($returnTag)) {
                    $type = $returnTag[0]->getType();
                    $typeName = (string) $type;

                    // Проверяем версию PHP
                    if ('true' === $typeName && version_compare($this->targetPhpVersion, '8.2.0', '<')) {
                        $typeName = 'bool'; // Заменяем true на bool для совместимости
                    }

                    if ('$this' === $typeName) {
                        $method->returnType = new Identifier('static');

                        return;
                    }
                    // Если тип "void", добавляем его
                    if ('void' === $typeName) {
                        $method->returnType = new Identifier('void');

                        return;
                    }

                    // Если тип nullable, добавляем NullableType
                    if (str_starts_with($typeName, '?')) {
                        $innerTypeName = substr($typeName, 1); // Убираем "?"
                        $method->returnType = new NullableType(new Name($innerTypeName));

                        return;
                    }

                    // Добавляем простой тип
                    $method->returnType = new Name($typeName);
                }
            } catch (\Exception $e) {
                // Логируем ошибку, если PHPDoc некорректен
                return;
            }
        }
    }
}
