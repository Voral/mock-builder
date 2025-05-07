<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Mocker;

/**
 * MockTools is a trait that provides tools for controlling the behavior of mocked methods during testing.
 *
 * It allows setting return values, throwing exceptions, and tracking method calls. This trait is designed to be used
 * in classes that need to mock methods dynamically during testing.
 */
trait MockTools
{
    /**
     * @var array Tracks whether a method has been automatically registered.
     *            Keys are method names, and values are booleans indicating registration status.
     */
    protected static array $auto = [];

    /**
     * @var array Tracks the number of calls for each mocked method.
     *            Keys are method names, and values are the call counts.
     */
    protected static array $mockCounter = [];

    /**
     * @var MockDefinition[] Default mock definitions for methods.
     *                       Keys are method names, and values are instances of MockDefinition.
     */
    protected static array $mockDefault = [];

    /**
     * @var bool[] Indicates whether named mode is enabled for mocked methods.
     *             Keys are method names, and values are booleans.
     */
    protected static array $mockNamedMode = [];

    /**
     * @var array Stores all parameters passed to mocked methods across all calls.
     *            Keys are method names, and values are associative arrays where keys are indices (numeric or hash)
     *            and values are parameter sets.
     */
    protected static array $mockParams = [];

    /**
     * @var MockDefinition[][] Stores predefined mock definitions for methods.
     *                         Keys are method names, and values are arrays of MockDefinition objects.
     */
    protected static array $mockDefinitions = [];

    /**
     * @var array Caches reflection data for method parameters.
     *            Keys are method names, and values are arrays of default parameter values.
     */
    protected static array $reflectionMethodParams = [];

    protected static array $mockEntity = [];

    /**
     * Resets and configures mock data for a specific method.
     *
     * This method clears any existing mock data for the specified method and sets up new mock definitions.
     *
     * @param string              $methodName        the name of the method to configure
     * @param MockDefinition[]    $definitions       an array of MockDefinition objects defining the behavior of the mocked method
     * @param null|MockDefinition $defaultDefinition a default MockDefinition to use if no specific definition matches
     * @param bool                $namedMode         whether to use named mode (parameter-based indexing) for mock behavior
     *
     * @throws \ReflectionException
     */
    public static function cleanMockData(
        string $methodName,
        array $definitions = [],
        ?MockDefinition $defaultDefinition = null,
        bool $namedMode = false,
    ): void {
        if ($namedMode) {
            $parameters = static::getReflectionMethodParams($methodName);
        }
        static::$mockDefinitions[$methodName] = [];
        foreach ($definitions as $index => $definition) {
            if ($namedMode) {
                $index = static::getIndex($definition->getParams(), $parameters);
            }
            $definition->setIndex($index);
            static::$mockDefinitions[$methodName][$index] = $definition;
        }
        static::$mockCounter[$methodName] = 0;
        static::$mockDefault[$methodName] = $defaultDefinition;
        static::$mockNamedMode[$methodName] = $namedMode;
    }

    /**
     * Generates a unique index (hash) based on the provided parameters.
     *
     * This method is used in named mode to generate a hash that uniquely identifies a set of parameters.
     *
     * @param array $params     the parameters passed to the mocked method
     * @param array $parameters the default parameter values for the method (from reflection)
     *
     * @return string a unique hash representing the parameters
     */
    private static function getIndex(array $params, array $parameters): string
    {
        $params = static::applyDefaultValues($params, $parameters);

        return hash('sha256', serialize($params));
    }

    /**
     * Retrieves the default parameter values for a method using reflection.
     *
     * This method caches the reflection data to avoid redundant computations.
     *
     * @param string $methodName the name of the method
     *
     * @return array an array of default parameter values for the method
     *
     * @throws \ReflectionException if the method does not exist or cannot be reflected
     */
    private static function getReflectionMethodParams(string $methodName): array
    {
        if (!isset(static::$reflectionMethodParams[$methodName])) {
            $reflectionMethod = new \ReflectionMethod(static::class, $methodName);
            $params = $reflectionMethod->getParameters();
            static::$reflectionMethodParams[$methodName] = [];
            foreach ($params as $parameter) {
                static::$reflectionMethodParams[$methodName][] = $parameter->isDefaultValueAvailable()
                    ? $parameter->getDefaultValue()
                    : null;
            }
        }

        return static::$reflectionMethodParams[$methodName];
    }

    /**
     * Applies default parameter values to the provided parameters.
     *
     * If a parameter is missing in the provided array, its default value (if available) is used.
     *
     * @param array $params               the parameters passed to the mocked method
     * @param array $reflectionParameters the default parameter values for the method (from reflection)
     *
     * @return array the updated parameters array with default values applied
     */
    private static function applyDefaultValues(array $params, array $reflectionParameters): array
    {
        $result = [];
        foreach ($reflectionParameters as $index => $parameter) {
            $result[] = array_key_exists($index, $params) ? $params[$index] : $parameter;
        }

        return $result;
    }

    /**
     * Executes a mocked method based on its configuration.
     *
     * This method determines the behavior of the mocked method by looking up the appropriate MockDefinition
     * based on the parameters or call index.
     *
     * @param string $methodName the name of the mocked method
     * @param array  $params     the parameters passed to the mocked method
     *
     * @return mixed the result of the mocked method, either predefined or default
     *
     * @throws \Throwable if an exception is configured for the current call
     */
    public static function executeMocked(string $methodName, array $params, ?object $entity = null): mixed
    {
        if (!isset(static::$mockCounter[$methodName])) {
            static::registerAuto($methodName);
        }

        if (static::$mockNamedMode[$methodName]) {
            $parameters = static::getReflectionMethodParams($methodName);
            $index = static::getIndex($params, $parameters);
        } else {
            $index = static::$mockCounter[$methodName];
        }
        ++static::$mockCounter[$methodName];
        static::$mockParams[$methodName][$index] = $params;
        static::$mockEntity[$methodName][$index] = $entity;
        $definition = static::$mockDefinitions[$methodName][$index] ?? static::$mockDefault[$methodName];
        if (null === $definition) {
            return null;
        }
        if (!empty($definition->getOutput())) {
            echo $definition->getOutput();
        }
        if (!empty($definition->getException())) {
            throw new ($definition->getException())();
        }

        return $definition->getResult();
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
        return static::$mockParams[$methodName][$index] ?? [];
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
        return static::$mockParams[$methodName] ?? [];
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
        return static::$mockCounter[$methodName] ?? 0;
    }

    public static function getMockedEntity(string $methodName): ?object
    {
        return static::$mockEntity[$methodName] ?? null;
    }

    /**
     * Automatically registers a method for mocking if it hasn't been explicitly configured.
     *
     * This method initializes default mock data for the specified method.
     *
     * @param string $methodName the name of the method to register
     *
     * @throws \ReflectionException
     */
    protected static function registerAuto(string $methodName): void
    {
        static::$auto[$methodName] = true;
        static::cleanMockData($methodName);
    }
}
