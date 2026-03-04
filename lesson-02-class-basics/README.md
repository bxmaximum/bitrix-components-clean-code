# Lesson 02: Базовый класс компонента

Первый шаг рефакторинга: переносим логику из процедурного `component.php` в класс.

## Что изменилось

| До | После |
|----|-------|
| Процедурный `component.php` | ООП `class.php` |
| Ручной `CPHPCache` | Встроенный `startResultCache()` |
| Параметры в начале файла | `onPrepareComponentParams()` |
| Нет обработки ошибок | `try/catch` |
| `CModule::IncludeModule` | `Bitrix\Main\Loader::includeModule()` |

## Что ещё не исправлено

- [ ] Старый API `CIBlockElement::GetList` вместо D7 ORM
- [ ] N+1 запросы (автор в цикле)
- [ ] `executeComponent()` слишком большой (нет декомпозиции)
- [ ] Нет `additionalCacheId` для кэша
- [ ] Форматирование смешано с получением данных

## Ключевые изменения в коде

### Валидация параметров

```php
public function onPrepareComponentParams($params): array
{
    $params["IBLOCK_ID"] = (int)($params["IBLOCK_ID"] ?? 0);
    $params["NEWS_COUNT"] = (int)($params["NEWS_COUNT"] ?? 10);
    return $params;
}
```

### Проверка модулей

```php
protected function checkModules(): void
{
    if (!Loader::includeModule("iblock")) {
        throw new \Bitrix\Main\SystemException("Модуль iblock не установлен");
    }
}
```

### Встроенное кэширование

```php
if ($this->startResultCache()) {
    // ... получение данных ...
    $this->includeComponentTemplate();
}
```

## Следующий шаг

Перейдите к [lesson-03-params-cache/](../lesson-03-params-cache/) для улучшения работы с параметрами и кэшем.
