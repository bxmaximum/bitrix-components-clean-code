# Lesson 03: Параметры и кэширование

Улучшаем валидацию параметров и защищаем кэш от утечек данных.

## Что изменилось

| До | После |
|----|-------|
| Простое приведение типов | Полная нормализация + проверка в `executeComponent` |
| Кэш без `additionalCacheID` | Кэш зависит от групп (`CACHE_GROUPS`) |
| Весь `$arResult` в кэше | `setResultCacheKeys()` — что оставить в файле кэша |
| `$USER` в шаблоне | `$arResult['IS_ADMIN']` **до** `includeComponentTemplate` |

## Ключевые изменения

### Нормализация без throw в prepare

```php
// onPrepareComponentParams — только контракт, без исключений
// (ядро вызывает prepare до executeComponent)

// executeComponent:
if ($this->arParams['IBLOCK_ID'] <= 0) {
    ShowError('Укажите корректный IBLOCK_ID');
    return;
}
```

### Защита от Cache Poisoning

```php
protected function getAdditionalCacheId(): array
{
    $currentUser = CurrentUser::get();
    $id = ['is_authorized' => $currentUser->getId() > 0];

    if ($this->arParams['CACHE_GROUPS'] !== 'N') {
        $id['user_groups'] = $currentUser->getUserGroups();
    }

    return $id;
}
```

### Разметка зависит от роли — флаг до шаблона

```php
$this->arResult['IS_ADMIN'] = CurrentUser::get()->isAdmin();
$this->setResultCacheKeys(['ITEMS', 'TOTAL_COUNT', 'IS_ADMIN']);
$this->includeComponentTemplate();

// Вне кэша — только SEO (не HTML шаблона)
$GLOBALS['APPLICATION']->SetTitle('Новости компании');
```

`setResultCacheKeys` задаёт, **какие ключи `$arResult` сохранить в файле кэша** (нужно для `component_epilog.php` и следующих хитов).

## Что ещё не исправлено

- [ ] Старый API `CIBlockElement::GetList` вместо D7 ORM
- [ ] N+1 запросы (автор в цикле)
- [ ] `executeComponent()` всё ещё большой
- [ ] Форматирование смешано с получением данных

## Следующий шаг

Перейдите к [lesson-04-decomposition/](../lesson-04-decomposition/) для разделения логики на методы.
