<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Mocker;

/**
 * MockTools is a trait that provides tools for controlling the behavior of mocked methods during testing.
 * It allows setting return values, throwing exceptions, and tracking method calls.
 */
trait MockTools
{
    /**
     * @var array tracks the number of calls for each mocked method
     */
    private static array $mockCounter = [];

    /**
     * @var array stores predefined results for mocked methods
     */
    private static array $mockResults = [];

    /**
     * @var array stores parameters passed to mocked methods during calls
     */
    private static array $mockParams = [];

    /**
     * @var array stores exceptions to be thrown by mocked methods
     */
    private static array $mockExceptions = [];

    /**
     * @var array stores default return values for mocked methods
     */
    private static array $mockDefaultResults = [];

    /**
     * @var array indicates whether named mode is enabled for mocked methods
     */
    private static array $mockNamedMode = [];

    /**
     * @var array Stores predefined outputs (e.g., echo statements) for mocked methods.
     */
    private static array $mockOutputs = [];

    /**
     * Resets and configures mock data for a specific method.
     *
     * @param string $methodName    the name of the method to configure
     * @param array  $results       a list of predefined results to return on subsequent calls
     * @param array  $exceptions    a list of exceptions to throw on specific calls
     * @param mixed  $defaultResult the default result to return when no predefined results are available
     * @param bool   $namedMode     whether to use named mode (parameter-based indexing) for mock behavior
     * @param array  $outputs       A list of predefined outputs (e.g., echo statements) for the mocked method.
     */
    public static function cleanMockData(
        string $methodName,
        array $results = [],
        array $exceptions = [],
        mixed $defaultResult = null,
        bool $namedMode = false,
        array $outputs = [],
    ): void {
        self::$mockCounter[$methodName] = 0;
        self::$mockResults[$methodName] = $results;
        self::$mockParams[$methodName] = [];
        self::$mockExceptions[$methodName] = $exceptions;
        self::$mockDefaultResults[$methodName] = $defaultResult;
        self::$mockNamedMode[$methodName] = $namedMode;
        self::$mockOutputs[$methodName] = $outputs;
    }

    /**
     * Generates a hash for a given set of parameters.
     *
     * This method is used in named mode to associate mock behavior with specific parameter sets.
     *
     * @param array $params the parameters to hash
     *
     * @return string a SHA-256 hash of the serialized parameters
     */
    public static function paramHash(array $params): string
    {
        return hash('sha256', serialize($params));
    }

    /**
     * Executes a mocked method based on its configuration.
     *
     * This method is called internally by mocked methods to determine their behavior.
     *
     * @param string $methodName the name of the mocked method
     * @param array  $params     the parameters passed to the mocked method
     *
     * @return mixed the result of the mocked method, either predefined or default
     *
     * @throws \Throwable if an exception is configured for the current call
     */
    public static function executeMocked(string $methodName, array $params): mixed
    {
        $index = self::$mockNamedMode[$methodName] ? self::paramHash($params) : self::$mockCounter[$methodName];
        ++self::$mockCounter[$methodName];

        self::$mockParams[$methodName][$index] = $params;
        if (isset(self::$mockExceptions[$methodName][$index])) {
            throw new self::$mockExceptions[$methodName][$index]();
        }

        if (!empty(self::$mockOutputs[$methodName][$index])) {
            echo self::$mockOutputs[$methodName][$index];
        }

        return self::$mockResults[$methodName][$index] ?? self::$mockDefaultResults[$methodName] ?? null;
    }

    /**
     * Retrieves the parameters passed to a specific call of a mocked method.
     *
     * @param string     $methodName the name of the mocked method
     * @param int|string $index      the index of the call (numeric or hash in named mode)
     *
     * @return array the parameters passed during the specified call
     */
    public static function getMockedParams(string $methodName, int|string $index): array
    {
        return self::$mockParams[$methodName][$index] ?? [];
    }

    /**
     * Retrieves all parameters passed to a mocked method across all calls.
     *
     * @param string $methodName the name of the mocked method
     *
     * @return array an associative array where keys are call indices and values are parameter sets
     */
    public static function getMockedParamsAll(string $methodName): array
    {
        return self::$mockParams[$methodName] ?? [];
    }

    /**
     * Retrieves the number of times a mocked method has been called.
     *
     * @param string $methodName the name of the mocked method
     *
     * @return int the total number of calls made to the mocked method
     */
    public static function getMockedCounter(string $methodName): int
    {
        return self::$mockCounter[$methodName] ?? 0;
    }
}
