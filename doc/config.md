# Configuration File

The configuration file `.vs-mock-builder.php` is used to set up utility parameters. It must return an array with the configuration. If the file exists, its values are used as default settings, but they can be overridden via command-line arguments.

## Available Parameters

### Required Parameters:
- **`basePath`** (array of strings)  
  Paths to source files or directories that need to be processed. Can be a string or an array of strings.  
  Example:
  ```php
  'basePath' => [
      '/path/to/source1',
      '/path/to/source2',
  ],
  ```

### Optional Parameters:
- **`targetPath`** (string)  
  The target directory where transformed files will be saved. If not specified, the default path `./target/` in the current working directory is used.  
  Example:
  ```php
  'targetPath' => __DIR__ . '/target/',
  ```

- **`cachePath`** (string)  
  The path to the directory for storing cache. If not specified, the default path `./.mock-builder-cache/` in the current working directory is used.  
  Example:
  ```php
  'cachePath' => __DIR__ . '/.mock-builder-cache/',
  ```

- **`classNameFilter`** (array of strings)  
  A list of substrings to filter classes. Only classes whose names contain at least one of the specified substrings will be processed. If not specified, all classes are processed.  
  Example:
  ```php
  'classNameFilter' => [
      'Service',
      'Manager',
  ],
  ```

- **`resultTypes`** (associative array)  
  Explicitly defined return types for methods. Keys are fully qualified method names (in the format `ClassName::methodName`), and values are their return types.  
  Example:
  ```php
  'resultTypes' => [
      '\Vendor\System\MyClass::myMethod' => 'string',
      '\Vendor\Other\AnotherClass::anotherMethod' => '?int',
  ],
  ```

- **`visitors`** (array of objects)  
  A list of visitors that will be applied to modify the AST. Visitors must be instances of classes inherited from `\Vasoft\MockBuilder\Visitor\ModuleVisitor`.  
  Example:
  ```php
  'visitors' => [
      new \Vasoft\MockBuilder\Visitor\PublicAndConstFilter(true),
      new \Vasoft\MockBuilder\Visitor\SetReturnTypes('8.1', true),
      new \Vasoft\MockBuilder\Visitor\AddMockToolsVisitor('App', true),
  ],
  ```

### Overriding via Command Line
Some parameters from the configuration file can be overridden via command-line options. For example:
- `-b` or `--base`: Overrides `basePath`.
- `-t` or `--target`: Overrides `targetPath`.
- `-f` or `--filter`: Overrides `classNameFilter`.

---

## Example Configuration File

```php
<?php

declare(strict_types=1);

use Vasoft\MockBuilder\Visitor\AddMockToolsVisitor;
use Vasoft\MockBuilder\Visitor\PublicAndConstFilter;
use Vasoft\MockBuilder\Visitor\SetReturnTypes;

return [
    'basePath' => [
        '/path/to/source1',
        '/path/to/source2',
    ],
    'targetPath' => __DIR__ . '/target/',
    'cachePath' => __DIR__ . '/.mock-builder-cache/',
    'classNameFilter' => [
        'Service',
        '\Vendor\Other\Manager',
    ],
    'resultTypes' => [
        '\Vendor\System\MyClass::myMethod' => 'string',
        '\Vendor\Other\AnotherClass::anotherMethod' => '?int',
    ],
    'visitors' => [
        new PublicAndConstFilter(true),
        new SetReturnTypes('8.1', true),
        new AddMockToolsVisitor('App', true),
    ],
];
```

This example demonstrates a full configuration using all available parameters.