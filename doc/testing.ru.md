# Тестирование с использованием трейта `MockTools`

Трейт `MockTools` предоставляет инструменты для управления поведением моков во время тестирования. Он особенно полезен в
случаях, когда тестируемый код не поддерживает внедрение зависимостей (Dependency Injection), и классы используются
внутри других классов напрямую. В таких ситуациях моки позволяют контролировать поведение этих внутренних классов.

В этой статье мы рассмотрим, как использовать трейт `MockTools` для написания тестов с помощью PHPUnit, а также упомянем
случаи, где он наиболее эффективен.

## Описание класса `MockDefinition`

Класс `MockDefinition` используется для определения поведения моков. Он инкапсулирует параметры, возвращаемые значения,
исключения и другие аспекты, которые могут быть связаны с вызовом метода.

### Основные компоненты `MockDefinition`:

1. **Параметры (`params`):**
    - Массив параметров, которые ожидает метод. В режиме `namedMode` эти параметры используются для генерации
      уникального хеша, который связывает определение с конкретным вызовом.

2. **Возвращаемое значение (`result`):**
    - Значение, которое будет возвращено при вызове метода. Может быть любого типа: массивом, строкой, числом или
      объектом.

3. **Исключение (`exception`):**
    - Класс исключения, который будет выброшен при вызове метода. Это полезно для тестирования обработки ошибок.

4. **Выходные данные (`output`):**
    - Строка, которая будет выведена (через `echo`) при вызове метода. Это может быть использовано для
      тестирования вывода данных.

5. **Уникальный индекс (`index`):**
    - Хеш или числовой индекс, который связывает определение с конкретным вызовом метода. В режиме `namedMode` индекс
      генерируется автоматически на основе параметров.

### Пример создания `MockDefinition`:

```php
$definition = new MockDefinition(
    params: [1], // Параметры метода
    result: ['id' => 1, 'name' => 'John Doe'], // Возвращаемое значение
    exception: null, // Исключение (если необходимо)
    output: null // Выходные данные (если необходимо)
);
```

`MockDefinition` является ключевым компонентом для настройки моков с помощью метода `cleanMockData`. Его использование
позволяет явно задавать поведение метода для каждого набора параметров, что делает тестирование более точным и
контролируемым.

--- 

## Основные возможности трейта `MockTools`

1. **Настройка возвращаемых значений:**
    - Вы можете задать фиксированные значения, которые будут возвращаться при вызове метода.
    - Поддерживается режим по умолчанию (default) и режим с именованными параметрами.

2. **Выброс исключений:**
    - Можно настроить метод так, чтобы он выбрасывал исключение при определённых вызовах.

3. **Проверка параметров:**
    - После выполнения теста можно проверить, какие параметры были переданы в метод.

4. **Счётчик вызовов:**
    - Трейт отслеживает количество вызовов каждого метода.

---

## Пример использования

### 1. Настройка мока

Допустим, у нас есть класс `UserService`, который мы хотим протестировать:

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

Метод `getUser` использует `self::executeMocked`, что позволяет нам управлять его поведением через трейт `MockTools`.

---

### 2. Написание теста

Ниже приведён пример теста с использованием PHPUnit:

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

        // Настройка мока с использованием MockDefinition
        $definition1 = new MockDefinition([1], ['id' => 1, 'name' => 'John Doe']);
        $definition2 = new MockDefinition([2], ['id' => 2, 'name' => 'Jane Doe']);
        $defaultDefinition = new MockDefinition(result: ['id' => 0, 'name' => 'Unknown']);

        // Настройка мока
        UserService::cleanMockData(
            'getUser',
            definitions: [$definition1, $definition2],
            defaultDefinition: $defaultDefinition,
        );
    }

    public function testGetUser(): void
    {
        // Первый вызов
        $result = $this->userService->getUser(1);
        $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $result);

        // Второй вызов
        $result = $this->userService->getUser(2);
        $this->assertEquals(['id' => 2, 'name' => 'Jane Doe'], $result);

        // Третий вызов (возвращается значение по умолчанию)
        $result = $this->userService->getUser(3);
        $this->assertEquals(['id' => 0, 'name' => 'Unknown'], $result);

        // Проверка количества вызовов
        $this->assertEquals(3, UserService::getMockedCounter('getUser'));

        // Проверка параметров первого вызова
        $params = UserService::getMockedParams('getUser', $definition1->getIndex());
        $this->assertEquals([1], $params);
    }

    public function testExceptionHandling(): void
    {
        // Настройка мока для выброса исключения
        $defaultDefinition = new MockDefinition(exception: \InvalidArgumentException::class);
        UserService::cleanMockData('getUser', defaultDefinition: $defaultDefinition);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid user ID');

        // Вызов метода, который выбросит исключение
        $this->userService->getUser(999);
    }
}
```

---

### 3. Объяснение кода

1. **Настройка мока (`cleanMockData`):**
    - Метод `cleanMockData` используется для задания поведения мока.
    - Параметр `definitions` принимает массив объектов `MockDefinition`, каждый из которых определяет поведение для
      конкретного набора параметров.
    - Параметр `defaultDefinition` задаёт значение, которое будет возвращаться, если подходящее определение не найдено.

2. **Проверка результатов:**
    - `$this->assertEquals` используется для сравнения ожидаемого и фактического результата.
    - `$this->expectException` проверяет, что метод выбрасывает ожидаемое исключение.

3. **Проверка параметров:**
    - Метод `getMockedParams` возвращает параметры, переданные в конкретный вызов метода.
    - Индекс для проверки параметров генерируется через `$definition->getIndex()`.

4. **Счётчик вызовов:**
    - Метод `getMockedCounter` возвращает общее количество вызовов метода.

---

## Именованный режим

Именованный режим полезен, когда поведение метода должно зависеть от передаваемых параметров. Например, если метод
вызывается с разными аргументами, и вы хотите настроить уникальное поведение для каждого набора параметров.

Для этого используется объект `MockDefinition`, который автоматически генерирует хеш на основе переданных параметров.

### Пример использования именованного режима

```php
// Настройка мока с именованным режимом
$definition1 = new MockDefinition([1], ['id' => 1, 'name' => 'John Doe']);
$definition2 = new MockDefinition([2], ['id' => 2, 'name' => 'Jane Doe']);
$defaultDefinition = new MockDefinition(result: ['id' => 0, 'name' => 'Unknown']);
UserService::cleanMockData(
    'getUser',
    definitions: [$definition1, $definition2],
    defaultDefinition: $defaultDefinition,
    namedMode: true,
);

// Тестирование
public function testNamedMode(): void
{
    $result = $this->userService->getUser(1);
    $this->assertEquals(['id' => 1, 'name' => 'John Doe'], $result);

    $result = $this->userService->getUser(2);
    $this->assertEquals(['id' => 2, 'name' => 'Jane Doe'], $result);

    // Проверка, что метод не найдёт подходящего результата для неизвестного параметра
    $result = $this->userService->getUser(3);
    $this->assertNull($result);
}
```

### Решаемая проблема

Именованный режим особенно полезен, когда:

- Метод вызывается с разными параметрами, и нужно настроить уникальное поведение для каждого набора параметров.
- В тестируемом коде нет Dependency Injection, и классы используются внутри других классов напрямую. В таких случаях
  невозможно заменить реальные объекты на моки стандартными средствами PHPUnit.

---

## **Получение вызывающего объекта**

Иногда при тестировании важно узнать, какой именно экземпляр объекта вызвал метод. Например, если у вас есть
класс `Logger`, который используется в разных частях программы, вы можете захотеть проверить, какой объект инициировал
запись в лог.

Для этого трейт `MockTools` предоставляет возможность отслеживать вызывающие объекты. При каждом вызове мокированного
метода сохраняется ссылка на объект, вызвавший этот метод. Вы можете получить её с помощью метода `getMockedEntity`.

### Пример:

```php
class Service
{
    public function logMessage(string $message): void
    {
        self::executeMocked(__FUNCTION__, func_get_args(), $this);
    }
}

class LoggerTest extends TestCase
{
    public function testLogMessage(): void
    {
        $service = new Service();
        $service->logMessage('Test message');

        // Проверяем, что метод был вызван объектом $service
        $caller = Service::getMockedEntity('logMessage');
        $this->assertSame($service, $caller);
    }
}
```

В этом примере мы проверяем, что метод `logMessage` был вызван конкретным экземпляром `$service`. Это особенно полезно,
если один и тот же метод может вызываться разными объектами, и вам нужно убедиться, что вызов произошёл именно из
ожидаемого контекста.

---

## Заключение

Трейт `MockTools` значительно упрощает тестирование классов, предоставляя гибкие инструменты для управления поведением
моков. Особенно он полезен в случаях, когда тестируемый код не поддерживает внедрение зависимостей, и классы
используются внутри других классов.

Используйте примеры из этой статьи как основу для написания собственных тестов с помощью PHPUnit.