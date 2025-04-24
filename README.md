# Utility for Generating Class Mocks

[RU](README.ru.md)

## Description

This utility is designed to automatically generate "mock" class shells from PHP source code. It allows you to:

- Keep only public methods of classes.
- Clear method bodies to prepare them for use in tests.
- Save the transformed files into a directory structure compliant with the PSR-4 standard.

This tool is particularly useful for testing complex systems where:

- It is impossible or difficult to use standard mocking tools (e.g., PHPUnit).
- Dependency Injection is not supported.
- The PSR-4 standard is not fully implemented.

After processing the classes, you can add a special trait to the generated mocks to control their behavior during testing.

## Installation

```bash
composer require --dev voral/mock-builder
```

## Usage

### Via Command Line

You can run the utility using the following command:

```bash
php bin/vs-mock-builder.php [options]
```

#### Available Options:

- `-i, --file <path>`: Specify the path to a PHP file to process.
- `-d, --dir <path>`: Specify the path to a directory containing PHP files to process.
- `-b, --base <path>`: Specify the base path for source files (default is the current working directory).
- `-t, --target <path>`: Specify the target path for saving generated mocks (default is `./target/`).
- `-f, --filter <filter>`: Specify a filter to select classes by name (comma-separated).
- `-h, --help`: Display help information.

Examples:

```bash
# Process a single file
php bin/vs-mock-builder.php -i=/path/to/file.php -t=/custom/target/dir/ -f=Manager,Service

# Process all files in a directory
php bin/vs-mock-builder.php -d=/path/to/directory -t=/custom/target/dir/ -f=Controller
```

### Via Configuration File

You can create a configuration file named `.vs-mock-builder.php` in the root of your project. Example content:

```php
<?php

declare(strict_types=1);

return [
    'directoryPath' => '/',
    'basePath' => '/path/to/source/',
    'targetPath' => __DIR__ . '/target/',
    'classNameFilter' => [
        'Application',
    ],
];
```

If a configuration file exists, its values are used as default parameters. However, parameters passed via the command line take precedence.

## Features

1. **Processing Interfaces and Classes**:
    - The utility supports both classes and interfaces.
    - A separate file is created in the target directory for each class or interface.

2. **Class Filtering**:
    - You can specify a list of substrings to filter class names.
    - If no filter is specified, all classes will be processed.

3. **PSR-4 Compliance**:
    - Transformed files are saved in a directory structure corresponding to the class namespace.

4. **Method Cleanup**:
    - All public methods remain in the class, but their bodies are cleared (content is removed).

## Requirements

- PHP >= 8.1
- Composer

## License

The project is distributed under the MIT license. For details, see the [LICENSE](LICENSE) file.