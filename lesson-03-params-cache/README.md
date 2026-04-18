# Lesson 03: Параметры и кэширование

Улучшаем валидацию параметров и защищаем кэш от утечек данных.

## Что изменилось

| До | После |
|----|-------|
| Простое приведение типов | Полная валидация с исключениями |
| Кэш без `additionalCacheId` | Кэш зависит от групп пользователя |
| Весь `$arResult` в кэше | Выборочное кэширование через `setResultCacheKeys()` |
| Нет поддержки `CACHE_TYPE` | Полная поддержка A/Y/N |
| `$USER` в шаблоне | `$arResult["IS_ADMIN"]` подготовлен в компоненте |

## Ключевые изменения

### Валидация с исключениями

```php
if ($params["IBLOCK_ID"] <= 0) {
    throw new ArgumentException("IBLOCK_ID должен быть положительным числом");
}
```

### Защита от Cache Poisoning

```php
protected function getCacheId(): array
{
    $currentUser = CurrentUser::get();
    return [
        "user_groups" => $currentUser->getUserGroups(),
        "is_authorized" => $currentUser->getId() > 0,
    ];
}
```

### Выборочное кэширование

```php
// Кэшируем только данные, не персонализацию
$this->setResultCacheKeys(["ITEMS", "TOTAL_COUNT"]);
```

### Персонализация вне кэша

```php
// После блока кэширования
$this->setPersonalData();

protected function setPersonalData(): void
{
    $currentUser = CurrentUser::get();
    $this->arResult["IS_ADMIN"] = in_array(1, $currentUser->getUserGroups());
}
```

## Что ещё не исправлено

- [ ] Старый API `CIBlockElement::GetList` вместо D7 ORM
- [ ] N+1 запросы (автор в цикле)
- [ ] `executeComponent()` всё ещё большой
- [ ] Форматирование смешано с получением данных

## Следующий шаг

Перейдите к [lesson-04-decomposition/](../lesson-04-decomposition/) для разделения логики на методы.
