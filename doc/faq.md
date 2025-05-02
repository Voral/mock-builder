# Frequently Asked Questions (FAQ)

## Table of Contents

1. [What to Do with Classes in the Global Namespace?](#what-to-do-with-classes-in-the-global-namespace)
2. [How to Create Mocks for Functions in the Global Scope?](#how-to-create-mocks-for-functions-in-the-global-scope)

---

### What to Do with Classes in the Global Namespace?

The utility supports processing classes that are located in the global namespace (without `namespace`). However, such
classes require a special approach for autoloading since the PSR-4 standard does not support classes without a
namespace.

#### Solution: Using a Custom Autoloader

To load classes from the global namespace, you can create a custom autoloader. Here is an example implementation:

```php
// custom_autoloader.php
spl_autoload_register(function ($class) {
    // Check that the class is in the global namespace
    if (false === strpos($class, '\\')) {
        $filePath = __DIR__ . '/target/' . $class . '.php';
        if (file_exists($filePath)) {
            require_once $filePath;
        }
    }
});
```

#### Connecting the Autoloader

For the autoloader to work, it must be included in your application's initialization file (e.g., `bootstrap.php`):

```php
// bootstrap.php
require_once __DIR__ . '/custom_autoloader.php';
```

#### Notes

1. **File Name Conflicts**:
    - If you have multiple classes with the same name in the global namespace, this may lead to conflicts during
      autoloading.
    - The utility saves such classes in the `/target/` directory, but the user must resolve any potential conflicts
      themselves, for example, by renaming files or classes.

2. **Alternative: `"files"` Section in `composer.json`**:
    - If there are only a few global classes, they can be explicitly listed in the `"files"` section of
      the `composer.json` file:
      ```json
      {
          "autoload": {
            "files": [
              "target/GlobalClass.php",
              "target/AnotherClass.php"
          ]
        }
      }
      ```

---

### How to Create Mocks for Functions in the Global Scope?

The utility does not support automatic generation of mocks for functions in the global scope, as PHP does not allow
redefining functions directly. However, you can manually create mocks for such functions using the generated `MockTools`
trait. Here is a step-by-step guide:

#### 1. Create a Helper Class for Mocks

Create a class that uses the `MockTools` trait to manage mock behavior. For example:

```php
namespace App\Mocker;

class GlobalFunctionMock {
    use MockTools;
}
```

#### 2. Create a File with Mocks for Global Functions

Create a separate file (e.g., `test/function_mock.php`) where you define mocks for the required functions. Implement
each function so that it calls the corresponding method in the helper class. For example:

```php
function GetMessage(string $messageCode, array $replace = [], $language = null): string {
    return \App\Mocker\GlobalFunctionMock::executeMocked('GetMessage', [$messageCode, $replace, $language]);
}
```

This approach allows you to replace real functions with their mocks while maintaining control over their behavior.

#### 3. Example of a Testable Class

Suppose you have a class that uses the global function `GetMessage`:

```php
namespace App;

class A {
    public static function foo(): string {
        return GetMessage('test1') . GetMessage('test2');
    }
}
```

#### 4. Write a Test

Now you can write a test that configures the behavior of the mock for the `GetMessage` function:

```php
<?php

declare(strict_types=1);

namespace App;

use App\Mocker\GlobalFunctionMock;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class ATest extends TestCase {
    public function testFoo(): void {
        // Configure the default mock behavior
        $definition = new MockDefinition(result: 'test');
        // Configure the mock for the GetMessage function
        GlobalFunctionMock::cleanMockData('GetMessage', defaultDefinition: $definition);

        // Call the testable method
        $result = A::foo();

        // Verify the result
        self::assertSame('testtest', $result);

        // Verify the number of function calls
        self::assertSame(2, GlobalFunctionMock::getMockedCounter('GetMessage'));

        // Verify the call parameters
        $params = GlobalFunctionMock::getMockedParamsAll('GetMessage');
        self::assertSame('test1', $params[0][0]); // First call with parameter 'test1'
        self::assertSame('test2', $params[1][0]); // Second call with parameter 'test2'
    }
}
```

#### Notes

1. **Manual Mock Creation**:
    - The utility cannot automatically handle functions in the global scope, so you need to manually create mocks for
      such functions.
    - Ensure that mocks are loaded before the testable code runs (e.g., via autoloading or explicit file inclusion).

2. **Test Isolation**:
    - Use the `cleanMockData` method to reset the state of mocks before each test to avoid side effects.

3. **Flexible Configuration**:
    - You can configure mock behavior by specifying predefined results, exceptions, or default values.