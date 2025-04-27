# Конфигурационный файл

Конфигурационный файл `.vs-mock-builder.php` используется для настройки параметров утилиты. Он должен возвращать массив с конфигурацией. Если файл существует, его значения используются как настройки по умолчанию, но их можно переопределить через командную строку.

## Доступные параметры

### Обязательные параметры:
- **`basePath`** (массив строк)  
  Пути к исходным файлам или директориям, которые необходимо обработать. Может быть строкой или массивом строк.
  Пример:
  ```php
  'basePath' => [
      '/path/to/source1',
      '/path/to/source2',
  ],
  ```

### Необязательные параметры:
- **`targetPath`** (строка)  
  Целевая директория, куда будут сохраняться преобразованные файлы. Если не указан, используется путь `./target/` в текущей рабочей директории.  
  Пример:
  ```php
  'targetPath' => __DIR__ . '/target/',
  ```

- **`cachePath`** (строка)  
  Путь к директории для хранения кэша. Если не указан, используется путь `./.mock-builder-cache/` в текущей рабочей директории.  
  Пример:
  ```php
  'cachePath' => __DIR__ . '/.mock-builder-cache/',
  ```

- **`classNameFilter`** (массив строк)  
  Список подстрок для фильтрации классов. Только классы, имена которых содержат хотя бы одну из указанных подстрок, будут обработаны. Если не указан, обрабатываются все классы.  
  Пример:
  ```php
  'classNameFilter' => [
      'Service',
      'Manager',
  ],
  ```

- **`visitors`** (массив объектов)  
  Список посетителей (visitors), которые будут применяться для модификации AST. Посетители должны быть экземплярами классов, унаследованных от `\Vasoft\MockBuilder\Visitor\ModuleVisitor`.  
  Пример:
  ```php
  'visitors' => [
      new \Vasoft\MockBuilder\Visitor\PublicAndConstFilter(true),
      new \Vasoft\MockBuilder\Visitor\SetReturnTypes('8.1', true),
      new \Vasoft\MockBuilder\Visitor\AddMockToolsVisitor('App', true),
  ],
  ```

### Переопределение через командную строку
Некоторые параметры из конфигурационного файла могут быть переопределены через ключи командной строки. Например:
- `-b` или `--base`: Переопределяет `basePath`.
- `-t` или `--target`: Переопределяет `targetPath`.
- `-f` или `--filter`: Переопределяет `classNameFilter`.

---

## Пример конфигурационного файла

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
        'Manager',
    ],
    'visitors' => [
        new PublicAndConstFilter(true),
        new SetReturnTypes('8.1', true),
        new AddMockToolsVisitor('App', true),
    ],
];
``` 

Этот пример демонстрирует полную конфигурацию с использованием всех доступных параметров.