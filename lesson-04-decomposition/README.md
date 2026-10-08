# Lesson 04: Декомпозиция

Разбиваем монолитный `executeComponent()` на отдельные методы с чёткой ответственностью.

## Что изменилось

| До | После |
|----|-------|
| `CIBlockElement::GetList` | D7 ORM `ElementNewsTable::getList()` |
| N+1 запросы (автор в цикле) | Один запрос через `UserTable::getList()` |
| Всё в `executeComponent()` | Декомпозиция на методы |
| Форматирование в цикле получения | Отдельный метод `formatItems()` |
| «Готовый» DETAIL_PAGE_URL из ORM | `IBLOCK.DETAIL_PAGE_URL` + `CIBlock::ReplaceDetailUrl` |
| Тег кэша ставил `CIBlockResult` | `CIBlock::registerWithTagCache()` явно — ORM тег не ставит |

## Требование: API-код инфоблока

Класс `ElementNewsTable` генерируется ядром, когда у инфоблока указан **API-код `News`**.

Админка → Инфоблоки → ваш инфоблок → поле «Символьный код API» → `News`.

Параметр `IBLOCK_ID` компоненту больше не нужен: ID инфоблока отдаёт `ElementNewsTable::getEntity()->getIblock()->getId()`.

## Архитектура методов

```
executeComponent()           ← Дирижёр (~30 строк)
    ├── getRawData()         ← Только ORM (ElementNewsTable)
    ├── loadAuthors()        ← Batch-загрузка авторов
    ├── formatItems()        ← Трансформация + ReplaceDetailUrl
    └── setSeo()             ← Мета-теги (вне кэша)

IS_ADMIN считается внутри блока кэша (до шаблона), группы — в additionalCacheID.
```

## Следующий шаг

Перейдите к `lesson-05-inheritance/` для выноса общей логики в модуль `project.core`.
