# Примеры компонента: эволюция от legacy к архитектуре

Пошаговая трансформация одного компонента «список новостей» — от типичного кода 2015 года до production-ready решения.

**Курс на BXMax:** [Чистый код в компонентах Битрикса: class.php](https://bxmax.ru/courses/clean-components-class-php)

## Как использовать

Открывайте папки по порядку и сравнивайте diff между соседними версиями:

```bash
diff -u lesson-01-legacy/component.php lesson-02-class-basics/step-1-migration/class.php
diff -u lesson-03-params-cache/class.php lesson-04-decomposition/class.php
diff -u lesson-04-decomposition/class.php lesson-05-inheritance/component/class.php
```

## Структура

| Папка | Урок | Ключевые изменения |
|-------|------|-------------------|
| `lesson-01-legacy/` | 1 | Процедурный `component.php`, CPHPCache, N+1, global |
| `lesson-02-class-basics/` | 2 | `class.php`, `onPrepareComponentParams`, `startResultCache` |
| `lesson-03-params-cache/` | 3 | Контракт параметров, `additionalCacheID`, `setResultCacheKeys` |
| `lesson-04-decomposition/` | 4 | `ElementNewsTable`, `ReplaceDetailUrl`, декомпозиция, batch authors |
| `lesson-05-inheritance/` | 5 | Модуль `project.core`, `BaseComponent`, трейт `WithSeo` |
| `lesson-06-final/` | 6 | `.description.php`, `.parameters.php`, финальный `class.php` |

## Метрики lesson-01 vs lesson-06

| | Legacy | Final |
|---|--------|-------|
| Строк в файле компонента | 114 | 128 |
| Самый длинный кусок логики | 97 строк (весь файл — один поток) | 26 строк (`executeComponent()`) |
| SQL на 10 новостях, автор — тот, кто смотрит | 32 | 7 |
| SQL на 10 новостях, автор — другой пользователь | 41 | 7 |
| SQL на 50 новостях, автор — другой пользователь | 201 | 7 |
| Глобальные переменные | `$USER` и `$APPLICATION` в трёх файлах | `$APPLICATION` в `WithSeo` (штатный SEO API) |
| Типизация | нет | да |

Финал длиннее legacy: прибавились `declare(strict_types=1)`, типы, контракт параметров и разбиение одного потока на пять методов. Часть кода уехала в модуль `project.core`. Числа строк — `wc -l` по `lesson-01-legacy/component.php` и `lesson-06-final/class.php`.

SQL посчитаны на `main` 26.800.0, `iblock` 26.0.100, PHP 8.4, с выключенным кэшем компонента, по запросам с трассировкой на файлы компонента. Из семи запросов финала пять — сборка класса `ElementNewsTable` ядром (только при промахе кэша компонента), два — новости и авторы. `CUser::GetByID()` в legacy не ходит в базу за текущим пользователем, отсюда разница между 32 и 41.

## Установка финальной версии

1. Скопируйте `lesson-05-inheritance/module/` → `/local/modules/project.core/`
2. Установите модуль: **Marketplace → Установленные решения** (`/bitrix/admin/partner_modules.php`). В «Настройки → Модули» модули с точкой в ID не показываются
3. Скопируйте `lesson-06-final/` → `/local/components/project/news.list/`
4. У инфоблока укажите **символьный код API `News`**
5. Вызовите компонент на странице

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

## Важно: API-код инфоблока

С урока 4 используется `\Bitrix\Iblock\Elements\ElementNewsTable`. Класс генерируется ядром, когда у инфоблока указан **API-код `News`**.

1. Админка → Контент → Инфоблоки → ваш инфоблок новостей
2. Поле **«Символьный код API»** → `News`
3. Сохранить

`DETAIL_PAGE_URL` как готовый URL из ORM **не приходит**. Берите шаблон через `'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL'` и собирайте URL в `formatItems()` через `\CIBlock::ReplaceDetailUrl(...)`.

Свойства инфоблока (например, `SOURCE`) выбираются тем же запросом, в отличие от универсального `ElementTable`. Берите значение по связи: `'SOURCE_VALUE' => 'SOURCE.VALUE'`. Голый `'SOURCE'` в `select` вернёт ключи вида `IBLOCK_ELEMENTS_ELEMENT_NEWS_SOURCE_VALUE`.

`ElementNewsTable::getList()`, в отличие от `CIBlockElement::GetList()`, не регистрирует тег кэша `iblock_id_N`. Поэтому с урока 4 компонент вызывает `\CIBlock::registerWithTagCache()` с ID, который знает сама сущность, внутри блока кэша, иначе правка новости не сбросит кэш.

Параметр `IBLOCK_ID` с урока 4 не нужен: выборка `ElementNewsTable` всегда идёт из инфоблока с API-кодом `News`, а его ID отдаёт `ElementNewsTable::getEntity()->getIblock()->getId()`.
