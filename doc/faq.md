# Frequently Asked Questions (FAQ)

## Table of Contents

1. [What to Do with Classes in the Global Namespace?](#what-to-do-with-classes-in-the-global-namespace)

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