# Testing with the `MockTools` Trait

The `MockTools` trait provides tools for managing the behavior of mocks during testing. It is especially useful in cases
where the code being tested does not support Dependency Injection, and classes are used directly inside other classes.
In such situations, mocks allow you to control the behavior of these internal classes.

In this article, we will explore how to use the `MockTools` trait to write tests with PHPUnit, as well as mention cases
where it is most effective.

## Description of the `MockDefinition` Class

The `MockDefinition` class is used to define the behavior of mocks. It encapsulates parameters, return values,
exceptions, and other aspects that may be associated with a method call.

### Key Components of `MockDefinition`:

1. **Parameters (`params`):**
    - An array of parameters that the method expects. In `namedMode`, these parameters are used to generate a unique
      hash that links the definition to a specific call.

2. **Return Value (`result`):**
    - The value that will be returned when the method is called. It can be of any type: an array, string, number, or
      object.

3. **Exception (`exception`):**
    - The exception class that will be thrown when the method is called. This is useful for testing error handling.

4. **Output Data (`output`):**
    - A string that will be output (e.g., via `echo`) when the method is called. This can be used to test data output.

5. **Unique Index (`index`):**
    - A hash or numeric index that links the definition to a specific method call. In `namedMode`, the index is
      automatically generated based on the parameters.

### Example of Creating a `MockDefinition`:

```php
$definition = new MockDefinition(
    params: [1], // Method parameters
    result: ['id' => 1, 'name' => 'John Doe'], // Return value
    exception: null, // Exception (if needed)
    output: null // Output data (if needed)
);
```

`MockDefinition` is a key component for configuring mocks using the `cleanMockData` method. Its use allows you to
explicitly define the behavior of a method for each set of parameters, making testing more precise and controllable.

---

## Key Features of the `MockTools` Trait

1. **Setting Return Values:**
    - You can define fixed values that will be returned when a method is called.
    - Both default mode and named parameter mode are supported.

2. **Throwing Exceptions:**
    - You can configure a method to throw an exception under certain calls.

3. **Checking Parameters:**
    - After running a test, you can verify which parameters were passed to the method.

4. **Call Counter:**
    - The trait tracks the number of calls made to each method.

---

## Usage Example

### 1. Setting Up a Mock

Suppose we have a `UserService` class that we want to test:

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

The `getUser` method uses `self::executeMocked`, which allows us to control its behavior through the `MockTools` trait.

---

### 2. Writing a Test

Below is an example of a test using PHPUnit:

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

        // Setting up a mock using MockDefinition
        $definition1 = new MockDefinition([1], ['id' => 1, 'name' => 'John Doe']);
        $definition2 = new MockDefinition([2], ['id' => 2, 'name' => 'Jane Doe']);
        $defaultDefinition = new MockDefinition(result: ['id' => 0, 'name' => 'Unknown']);

        // Configuring the mock
        UserService::cleanMockData(
            'getUser',
            definitions: [$definition1, $definition2],
            defaultDefinition: $defaultDefinition,
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

        // Checking the number of calls
        $this->assertEquals(3, UserService::getMockedCounter('getUser'));

        // Checking the parameters of the first call
        $params = UserService::getMockedParams('getUser', $definition1->getIndex());
        $this->assertEquals([1], $params);
    }

    public function testExceptionHandling(): void
    {
        // Configuring the mock to throw an exception
        $defaultDefinition = new MockDefinition(exception: \InvalidArgumentException::class);
        UserService::cleanMockData('getUser', defaultDefinition: $defaultDefinition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user ID');

        // Calling the method, which will throw an exception
        $this->userService->getUser(999);
    }
}
```

---

### 3. Explanation of the Code

1. **Configuring the Mock (`cleanMockData`):**
    - The `cleanMockData` method is used to define the behavior of the mock.
    - The `definitions` parameter accepts an array of `MockDefinition` objects, each defining the behavior for a
      specific set of parameters.
    - The `defaultDefinition` parameter specifies the value that will be returned if no matching definition is found.

2. **Verifying Results:**
    - `$this->assertEquals` is used to compare the expected and actual results.
    - `$this->expectException` checks that the method throws the expected exception.

3. **Checking Parameters:**
    - The `getMockedParams` method returns the parameters passed to a specific method call.
    - The index for checking parameters is generated via `$definition->getIndex()`.

4. **Call Counter:**
    - The `getMockedCounter` method returns the total number of calls made to the method.

---

## Named Mode

Named mode is useful when the behavior of a method should depend on the parameters passed. For example, if a method is
called with different arguments, and you want to configure unique behavior for each set of parameters.

For this purpose, the `MockDefinition` object is used, which automatically generates a hash based on the passed
parameters.

### Example of Using Named Mode

```php
// Configuring a mock with named mode
$definition1 = new MockDefinition([1], ['id' => 1, 'name' => 'John Doe']);
$definition2 = new MockDefinition([2], ['id' => 2, 'name' => 'Jane Doe']);
$defaultDefinition = new MockDefinition(result: ['id' => 0, 'name' => 'Unknown']);
UserService::cleanMockData(
    'getUser',
    definitions: [$definition1, $definition2],
    defaultDefinition: $defaultDefinition,
    namedMode: true,
);

// Testing
public function testNamedMode(): void
{
    $result = $this->userService->getUser(1);
    $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $result);

    $result = $this->userService->getUser(2);
    $this->assertEquals(['id' => 2, 'name' => 'Jane Doe'], $result);

    // Checking that the method will not find a matching result for an unknown parameter
    $result = $this->userService->getUser(3);
    $this->assertNull($result);
}
```

### Problem Solved

Named mode is especially useful when:

- A method is called with different parameters, and you need to configure unique behavior for each set of parameters.
- The code being tested does not support Dependency Injection, and classes are used directly inside other classes. In
  such cases, it is impossible to replace real objects with mocks using standard PHPUnit tools.

---

## Conclusion

The `MockTools` trait significantly simplifies testing classes by providing flexible tools for managing mock behavior.
It is especially useful in cases where the code being tested does not support Dependency Injection, and classes are used
inside other classes.

Use the examples from this article as a basis for writing your own tests with PHPUnit.