<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Mocker;

/**
 * MockDefinition represents the configuration for a mocked method.
 *
 * This class encapsulates the parameters, return value, exception, and output of a mocked method.
 * It is used to define the behavior of mocked methods during testing.
 */
class MockDefinition
{
    /**
     * @var int|string A unique hash or index identifying this definition.
     *                 Used to associate the definition with specific calls in named mode.
     */
    private int|string $hash = '';

    /**
     * @param array       $params    The parameters expected by the mocked method.
     *                               These are used to match calls in named mode.
     * @param mixed       $result    the value that the mocked method should return
     * @param null|string $exception The fully qualified class name of an exception to throw.
     *                               If set, the mocked method will throw this exception.
     * @param null|string $output    The output (e.g., echo statements) that the mocked method should produce.
     */
    public function __construct(
        private readonly array $params = [],
        private readonly mixed $result = null,
        private readonly ?string $exception = null,
        private readonly ?string $output = null,
    ) {}

    /**
     * Sets index or hash for this definition.
     *
     * This method is intended for internal use only and is automatically called by `cleanMockData`
     * to assign a unique index or hash based on the method's parameters.
     *
     * @param int|string $value the new index or hash value
     *
     * @return string the updated index or hash
     *
     * @internal this method should not be called directly by users
     */
    public function setIndex(int|string $value): string
    {
        $this->hash = $value;

        return $this->hash;
    }

    /**
     * Retrieves the unique index or hash for this definition.
     *
     * @return int|string the unique index or hash
     */
    public function getIndex(): int|string
    {
        return $this->hash;
    }

    /**
     * Retrieves the parameters expected by the mocked method.
     *
     * @return array the parameters array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Retrieves the result that the mocked method should return.
     *
     * @return mixed the return value of the mocked method
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Retrieves the exception that the mocked method should throw.
     *
     * @return null|string the fully qualified class name of the exception, or null if no exception is configured
     */
    public function getException(): ?string
    {
        return $this->exception;
    }

    /**
     * Retrieves the output (e.g., echo statements) that the mocked method should produce.
     *
     * @return null|string the output string, or null if no output is configured
     */
    public function getOutput(): ?string
    {
        return $this->output;
    }
}
