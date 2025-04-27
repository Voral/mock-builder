# Visitors

Visitors are classes that modify the Abstract Syntax Tree (AST) of the source code to generate mocks. The package
includes several built-in visitors, but it is also possible to create custom ones.

## Built-in Visitors

### 1. `PublicAndConstFilter`

**Purpose:**  
Keeps only public methods and constants in classes and traits. All other elements (protected, private methods, and
properties) are removed.

**Constructor Parameters:**

- `$skipThrowable` (bool, optional): If `true`, classes that are descendants of `Throwable` or `Exception` will be
  skipped.

**Example Usage:**

```php
new \Vasoft\MockBuilder\Visitor\PublicAndConstFilter(true);
```

---

### 2. `SetReturnTypes`

**Purpose:**  
Adds return types to methods based on PHPDoc comments. If a type is already specified, it remains unchanged.

**Constructor Parameters:**

- `$targetPhpVersion` (string, optional): The PHP version for which the types are generated. By default, the current PHP
  version is used.
- `$skipThrowable` (bool, optional): If `true`, classes that are descendants of `Throwable` or `Exception` will be
  skipped.

**Example Usage:**

```php
new \Vasoft\MockBuilder\Visitor\SetReturnTypes('8.1', true);
```

---

### 3. `AddMockToolsVisitor`

**Purpose:**  
Adds the special `MockTools` trait to each processed class or trait. This trait allows controlling the behavior of mocks
during testing.

**Constructor Parameters:**

- `$baseNamespace` (string): The base namespace where the `MockTools` trait will be copied.
- `$skipThrowable` (bool, optional): If `true`, classes that are descendants of `Throwable` or `Exception` will be
  skipped.

**Note:**  
The `MockTools` trait is copied into the target directory with an updated namespace. This makes the mocks autonomous, as
they no longer depend on the original package.

**Example Usage:**

```php
new \Vasoft\MockBuilder\Visitor\AddMockToolsVisitor('App', true);
```

---

## Creating Custom Visitors

You can create custom visitors to implement additional AST processing logic. To do this:

1. **Extend the `ModuleVisitor` class:**  
   All visitors must inherit from `\Vasoft\MockBuilder\Visitor\ModuleVisitor`.

2. **Implement AST processing methods:**
    - The `leaveNode($node)` method is called when exiting a node in the AST. You can modify nodes or perform checks
      here.
    - The `beforeProcess()` method is called before file processing begins. It can be used to perform preparatory
      actions.

3. **Add the visitor to the configuration:**  
   Add an instance of your visitor to the `visitors` array in the configuration file.

**Example of a Simple Visitor:**

```php
<?php

declare(strict_types=1);

namespace MyApp\Visitor;

use PhpParser\Node;
use Vasoft\MockBuilder\Visitor\ModuleVisitor;

class MyCustomVisitor extends ModuleVisitor
{
    public function leaveNode($node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            // Add a comment to each class
            $node->setAttribute('comments', [
                new \PhpParser\Comment("// This class was processed by MyCustomVisitor"),
            ]);
        }

        return null;
    }
}
```

**Adding to Configuration:**

```php
'visitors' => [
    new \MyApp\Visitor\MyCustomVisitor(),
],
```

---

## Conclusion

The built-in visitors provide a basic set of functionality for generating mocks, but you can always extend them by
creating custom visitors. This allows you to adapt the utility to the specific requirements of your project.