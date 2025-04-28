# Utility for Generating Class Mocks

[RU](README.ru.md)

## Description

This utility is designed to automatically generate "mock" shells of classes from PHP source code. It allows you to:

- Keep only public methods of classes.
- Clear method bodies to prepare them for use in tests.
- Save the transformed files into a directory structure compliant with the PSR-4 standard.

This tool is particularly useful for testing complex systems where:

- It is impossible or difficult to use standard mocking tools (e.g., PHPUnit).
- Dependency Injection is not supported.
- The PSR-4 standard is not fully implemented.

After processing the classes, you can add a special trait to the generated mocks to control their behavior during
testing. For more details on how to use this trait, see the section [Testing with the MockTools Trait](doc/testing.md).

---

## Installation

```bash
composer require --dev voral/mock-builder
```

---

## Usage

### Via Command Line

You can run the utility using the following command:

```bash
php bin/vs-mock-builder.php [options]
```

#### Available Options:

- `-b, --base <path>`: Specify the base path for source files (default is the current working directory).
- `-t, --target <path>`: Specify the target path for saving generated mocks (default is `./target/`).
- `-f, --filter <filter>`: Specify a filter to select classes by name (comma-separated).
- `-d, --display`: Display the progress of processing.
- `-c, --clear-cache`: Clear the class dependency graph cache.
- `-h, --help`: Display help information.

Examples:

```bash
# Process all files in a directory
php bin/vs-mock-builder.php -b=/path/to/source -t=/custom/target/dir/ -f=Controller
```

### Via Configuration File

You can create a configuration file `.vs-mock-builder.php` in the root of your project. An example of the file's content
and a description of all available parameters can be found in the section [Configuration File](doc/config.md).

If a configuration file exists, its values are used as default parameters. However, parameters passed via the command
line take precedence.

---

## Features

1. **Processing Interfaces, Classes, and Traits**:
    - The utility supports processing classes, interfaces, and traits.
    - A separate file is created in the target directory for each class or interface.

2. **Class Filtering**:
    - You can specify a list of substrings to filter class names.
    - If no filter is specified, all classes are processed.

3. **PSR-4 Compliance**:
    - Transformed files are saved in a directory structure corresponding to the class namespace.

4. **Method Cleanup**:
    - All public methods remain in the class, but their bodies are cleared (content is removed).

5. **Customization via Visitors**:
    - You can customize the AST processing using visitors. For more details on built-in and custom visitors, see the
      section [Visitors](doc/visitor.md).

---

## Requirements

- PHP >= 8.1
- Composer

---

## License

The project is distributed under the MIT license. For details, see the [LICENSE](LICENSE) file.

---

## Additional Information

- [Configuration File](doc/config.md): Detailed description of all configuration parameters.
- [Visitors](doc/visitor.md): Information about built-in and custom visitors.
- [Testing with the MockTools Trait](doc/testing.md): A guide to testing generated mocks.

---

## Frequently Asked Questions (FAQ)

1. [What to Do with Classes in the Global Namespace?](doc/faq.md#what-to-do-with-classes-in-the-global-namespace)
2. [How to Create Mocks for Functions in the Global Scope?](doc/faq.md#how-to-create-mocks-for-functions-in-the-global-scope)

---

### Changes

The change history can be found in the [CHANGELOG.md](CHANGELOG.md) file.