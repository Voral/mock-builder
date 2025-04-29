<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Visitor;

use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use Vasoft\MockBuilder\Config;
use Vasoft\MockBuilder\Graph;

/**
 * ModuleVisitor is an abstract base class for visitors that modify the Abstract Syntax Tree (AST) during code processing.
 * It provides common functionality and configuration options for all concrete visitors.
 */
abstract class ModuleVisitor extends NodeVisitorAbstract
{
    /**
     * @var null|Graph a graph of class dependencies used for analyzing inheritance and interfaces
     */
    protected ?Graph $dependenceGraph = null;

    /**
     * @var null|Config configuration object containing settings for the mock builder utility
     */
    protected ?Config $config = null;

    /**
     * Constructor for the ModuleVisitor class.
     *
     * @param bool $skipThrowable whether to skip classes that are instances of Throwable or Exception
     */
    public function __construct(
        protected readonly bool $skipThrowable = false,
    ) {}

    /**
     * Sets the dependency graph for analyzing class relationships.
     *
     * @param Graph $dependenciesGraph the dependency graph to use
     *
     * @return static returns the current instance for method chaining
     */
    public function setDependenceGraph(Graph $dependenciesGraph): static
    {
        $this->dependenceGraph = $dependenciesGraph;

        return $this;
    }

    /**
     * Determines whether a given node should be skipped during processing.
     *
     * @param mixed $node the AST node to check
     *
     * @return bool true if the node should be skipped, false otherwise
     */
    protected function needSkip($node): bool
    {
        return $this->skipThrowable && $node instanceof Class_ && $this->isThrowable($node);
    }

    /**
     * Checks if a class is an instance of Throwable or Exception.
     *
     * @param Class_ $class the class node to check
     *
     * @return bool true if the class is an instance of Throwable or Exception, false otherwise
     */
    private function isThrowable(Class_ $class): bool
    {
        if (null === $this->dependenceGraph || (empty($class->name) && empty($class->namespacedName))) {
            return false;
        }
        $className = $class->namespacedName?->toString() ?? $class->name->toString();

        return $this->dependenceGraph->isInstanceOf($className, ['Throwable', 'Exception']);
    }

    /**
     * Called before processing begins.
     * This method can be overridden by subclasses to perform preparatory actions.
     */
    public function beforeProcess(): void {}

    /**
     * Sets the configuration object for the visitor.
     *
     * @param Config $config the configuration object to use
     *
     * @return static returns the current instance for method chaining
     */
    public function setConfig(Config $config): static
    {
        $this->config = $config;

        return $this;
    }
}
