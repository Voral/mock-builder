# Testing with the `MockTools` Trait

The `MockTools` trait provides tools to control the behavior of mocks during testing. It is particularly useful in cases
where the code being tested does not support Dependency Injection, and classes are used internally within other classes.
In such scenarios, mocks allow you to control the behavior of these internal classes.

In this article, we will explore how to use the `MockTools` trait to write tests with PHPUnit, as well as discuss the
cases where it is most effective.

---

## Key Features of the `MockTools` Trait

1. **Setting Return Values:**
    - You can define fixed values that will be returned when a method is called.
    - Both default mode and named parameter mode are supported.

2. **Throwing Exceptions:**
    - You can configure a method to throw an exception under specific conditions.

3. **Parameter Verification:**
    - After executing a test, you can verify which parameters were passed to a method.

4. **Call Counter:**
    - The trait tracks the number of times each method is called.

---

## Example Usage

### 1. Setting Up the Mock

Suppose we have a class `UserService` that we want to test:

```php
<?php

namespace App;

class UserService
{
    public function getUser(int $id): array
    {
        return self::executeMocked(__FUNCTION__, func_get_args());
    }
}
```

The `getUser` method uses `self::executeMocked`, which allows us to control its behavior using the `MockTools` trait.

---

### 2. Writing the Test

Below is an example test using PHPUnit:

```php
<?php

use App\UserService;
use PHPUnit\Framework\TestCase;

class UserServiceTest extends TestCase
{
    protected UserService $userService;

    protected function setUp(): void
    {
        $this->userService = new UserService();

        // Setting up the mock
        UserService::cleanMockData(
            'getUser',
            results: [
                ['id' => 1, 'name' => 'John Doe'], // First call
                ['id' => 2, 'name' => 'Jane Doe'], // Second call
            ],
            defaultResult: ['id' => 0, 'name' => 'Unknown'], // Default result
        );
    }

    public function testGetUser(): void
    {
        // First call
        $result = $this->userService->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $result);

        // Second call
        $result = $this->userService->getUser(2);
        $this->assertEquals(['id' => 2, 'name' => 'Jane Doe'], $result);

        // Third call (returns the default value)
        $result = $this->userService->getUser(3);
        $this->assertEquals(['id' => 0, 'name' => 'Unknown'], $result);

        // Verifying the number of calls
        $this->assertEquals(3, UserService::getMockedCounter('getUser'));

        // Verifying the parameters of the first call
        $params = UserService::getMockedParams('getUser', 0);
        $this->assertEquals([1], $params);
    }

    public function testExceptionHandling(): void
    {
        // Setting up the mock to throw an exception
        UserService::cleanMockData(
            'getUser',
            exceptions: [new \InvalidArgumentException('Invalid user ID')],
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user ID');

        // Calling the method that will throw an exception
        $this->userService->getUser(999);
    }
}
```

---

### 3. Explanation of the Code

1. **Setting Up the Mock (`cleanMockData`):**
    - The `cleanMockData` method is used to define the behavior of the mock.
    - The `results` parameter specifies a sequence of return values.
    - The `defaultResult` parameter defines the value that will be returned if all values in `results` have been used.
    - The `exceptions` parameter allows you to configure exceptions to be thrown during method calls.

2. **Verifying Results:**
    - `$this->assertEquals` is used to compare the expected and actual results.
    - `$this->expectException` checks that the method throws the expected exception.

3. **Verifying Parameters:**
    - The `getMockedParams` method returns the parameters passed to a specific method call.
    - Index `0` corresponds to the first call, `1` to the second, and so on.

4. **Call Counter:**
    - The `getMockedCounter` method returns the total number of times a method was called.

---

## Named Mode

Named mode is useful when the behavior of a method depends on the parameters passed. For example, if a method is called
with different arguments, and you want to configure unique behavior for each set of parameters.

The `paramHash` method is used to create a hash based on the passed parameters. This hash serves as a key for selecting
the result.

### Example of Using Named Mode

```php
// Setting up the mock in named mode
UserService::cleanMockData(
    'getUser',
    results: [
        UserService::paramHash([1]) => ['id' => 1, 'name' => 'John Doe'],
        UserService::paramHash([2]) => ['id' => 2, 'name' => 'Jane Doe'],
    ],
    namedMode: true,
);

// Testing
public function testNamedMode(): void
{
    $result = $this->userService->getUser(1);
    $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $result);

    $result = $this->userService->getUser(2);
    $this->assertEquals(['id' => 2, 'name' => 'Jane Doe'], $result);

    // Verifying that no result is found for an unknown parameter
    $result = $this->userService->getUser(3);
    $this->assertNull($result);
}
```

### Problem Being Solved

Named mode is especially useful when:

- A method is called with different parameters, and you need to configure unique behavior for each set of parameters.
- The code being tested does not support Dependency Injection, and classes are used internally within other classes. In
  such cases, it is impossible to replace real objects with mocks using standard PHPUnit tools.

---

## Conclusion

The `MockTools` trait significantly simplifies testing by providing flexible tools to control the behavior of mocks. It
is particularly useful in cases where the code being tested does not support Dependency Injection, and classes are used
internally within other classes.

Use the examples from this article as a basis for writing your own tests with PHPUnit.