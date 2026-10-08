# Lesson 06: Финальная версия

Production-ready компонент после прохождения всего курса.

## Установка

1. Модуль из `../lesson-05-inheritance/module/` → `/local/modules/project.core/`
2. Установите модуль: **Marketplace → Установленные решения** (`/bitrix/admin/partner_modules.php`)
3. Эта папка → `/local/components/project/news.list/`
4. Инфоблок с **символьным кодом API `News`**

## Вызов

```php
$APPLICATION->IncludeComponent(
    'project:news.list',
    '',
    [
        'NEWS_COUNT' => 10,
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 3600,
        'CACHE_GROUPS' => 'Y',
    ]
);
```

## Сравните с legacy

```bash
diff -u ../lesson-01-legacy/component.php class.php
```
