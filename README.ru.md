# Утилита для генерации моков классов

[EN](README.md)

## Описание

Эта утилита предназначена для автоматической генерации "моков" (оболочек) классов из исходного PHP-кода. Она позволяет:

- Оставлять только публичные методы классов.
- Очищать тела методов для подготовки их к использованию в тестах.
- Сохранять преобразованные файлы в структуру директорий, соответствующую стандарту PSR-4.

Утилита особенно полезна для тестирования сложных систем, где:

- Невозможно или сложно использовать стандартные инструменты мокирования (например, PHPUnit).
- Не поддерживается внедрение зависимостей (Dependency Injection).
- Стандарт PSR-4 не полностью реализован.

После обработки классов вы можете добавить специальный трейт в сгенерированные моки для управления их поведением во
время тестирования. Подробнее о том, как использовать этот трейт, читайте в
разделе [Тестирование с использованием трейта MockTools](doc/testing.ru.md).

---

## Установка

```bash
composer require --dev voral/mock-builder
```

---

## Использование

### Через командную строку

Вы можете запустить утилиту, используя следующую команду:

```bash
php bin/vs-mock-builder.php [options]
```

#### Доступные параметры:

- `-b, --base <path>`: Указать базовый путь для исходных файлов (по умолчанию — текущая рабочая директория).
- `-t, --target <path>`: Указать целевой путь для сохранения сгенерированных моков (по умолчанию — `./target/`).
- `-f, --filter <filter>`: Указать фильтр для выбора классов по имени (через запятую).
- `-d, --display`: Отображать ходы выполнения обработки
- `-c, --clear-cache`: Очистить кеш графа классов
- `-h, --help`: Показать справку.

Примеры:

```bash
# Обработать все файлы в директории
php bin/vs-mock-builder.php -b=/path/to/source -t=/custom/target/dir/ -f=Controller
```

### Через конфигурационный файл

Вы можете создать конфигурационный файл `.vs-mock-builder.php` в корне вашего проекта. Пример содержимого файла и
описание всех доступных параметров можно найти в разделе [Конфигурационный файл](doc/config.ru.md).

Если конфигурационный файл существует, его значения используются как параметры по умолчанию. Однако параметры,
переданные через командную строку, имеют приоритет.

---

## Возможности

1. **Обработка интерфейсов, классов и трейтов**:
    - Утилита поддерживает обработку классов, интерфейсов и трейтов.
    - Для каждого класса или интерфейса создаётся отдельный файл в целевой директории.

2. **Фильтрация классов**:
    - Вы можете указать список подстрок для фильтрации имён классов.
    - Если фильтр не указан, обрабатываются все классы.

3. **Соответствие стандарту PSR-4**:
    - Преобразованные файлы сохраняются в структуру директорий, соответствующую пространству имён класса.

4. **Очистка методов**:
    - Все публичные методы остаются в классе, но их тела очищаются (удаляется содержимое).

5. **Настройка через визиторы**:
    - Вы можете настраивать процесс обработки AST с помощью визиторов. Подробнее о встроенных и пользовательских
      визиторах читайте в разделе [Визиторы](doc/visitor.ru.md).

---

## Требования

- PHP >= 8.1
- Composer

---

## Лицензия

Проект распространяется под лицензией MIT. Подробности см. в файле [LICENSE](LICENSE).

---

## Дополнительная информация

- [Конфигурационный файл](doc/config.ru.md): Подробное описание всех параметров конфигурации.
- [Визиторы](doc/visitor.ru.md): Информация о встроенных и пользовательских визиторах.
- [Тестирование с использованием трейта MockTools](doc/testing.ru.md): Руководство по тестированию сгенерированных
  моков.

---

# Часто задаваемые вопросы (FAQ)

1. [Как быть с классами в глобальном пространстве имён?](doc/faq.ru.md#как-быть-с-классами-в-глобальном-пространстве-имён)
2. [Как создавать моки для функций в глобальной области видимости?](doc/faq.ru.md#как-создавать-моки-для-функций-в-глобальной-области-видимости)

---

### Изменения

Историю изменений можно найти в файле [CHANGELOG.md](CHANGELOG.md).

### Todo

- [ ] Добавить тестирование всего пакета  
  _Необходимо покрыть unit-тестами все ключевые классы для дальнейшего развития проекта_
- [ ] Добавить возможность взаимодействия с моками привычными способами, как в PHPUnit  
  _Например, реализовать метод `getMock` с поддержкой `expects`._
