# Lesson 05: Наследование и трейты

Выносим общую логику в модуль `project.core`.

## Структура

```
lesson-05-inheritance/
├── module/                    → /local/modules/project.core/
│   ├── install/index.php
│   ├── install/version.php
│   └── lib/
│       ├── Component/BaseComponent.php
│       └── Traits/
│           └── WithSeo.php
└── component/                 → /local/components/project/news.list/
    ├── class.php
    └── templates/.default/
```

## Установка

1. Скопируйте `module/` в `/local/modules/project.core/`
2. Установите модуль: **Marketplace → Установленные решения** (`/bitrix/admin/partner_modules.php`)
3. Скопируйте `component/` в `/local/components/project/news.list/`

## Следующий шаг

`lesson-06-final/` — production-ready версия с `.parameters.php`.
