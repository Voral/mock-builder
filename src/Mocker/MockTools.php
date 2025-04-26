<?php

declare(strict_types=1);

namespace Vasoft\MockBuilder\Mocker;

trait MockTools
{
    private static array $mockCounter = [];
    private static array $mockResults = [];
    private static array $mockParams = [];
    private static array $mockExceptions = [];
    private static array $mockDefaultResults = [];
    private static array $mockNamedMode = [];

    public static function cleanMockData(
        string $methodName,
        array $results = [],
        array $exceptions = [],
        mixed $defaultResult = null,
        bool $namedMode = false,
    ): void {
        self::$mockCounter[$methodName] = 0;
        self::$mockResults[$methodName] = $results;
        self::$mockParams[$methodName] = [];
        self::$mockExceptions[$methodName] = $exceptions;
        self::$mockDefaultResults[$methodName] = $defaultResult;
        self::$mockNamedMode[$methodName] = $namedMode;
    }

    public static function paramHash(array $params): string
    {
        return hash('sha256', serialize($params));
    }

    private static function executeMocked(string $methodName, array $params): mixed
    {
        $index = self::$mockNamedMode[$methodName] ? self::paramHash($params) : self::$mockCounter[$methodName];
        ++self::$mockCounter[$methodName];

        self::$mockParams[$methodName][$index] = $params;
        if (isset(self::$mockExceptions[$methodName][$index])) {
            throw new self::$mockExceptions[$methodName][$index]();
        }

        return self::$mockResults[$methodName][$index] ?? self::$mockDefaultResults[$methodName] ?? null;
    }

    public static function getMockedParams(string $methodName, int|string $index): array
    {
        return self::$mockParams[$methodName][$index] ?? [];
    }

    public static function getMockedParamsAll(string $methodName): array
    {
        return self::$mockParams[$methodName] ?? [];
    }

    public static function getMockedCounter(string $methodName): int
    {
        return self::$mockCounter[$methodName];
    }
}
