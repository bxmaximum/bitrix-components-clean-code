<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * ЧТО ИЗМЕНИЛОСЬ В УРОКЕ 2:
 * 1. ИСПРАВЛЕНО: Подключение модулей переехало в NewsListComponent::checkModules()
 * 2. ВСЁ ЕЩЁ ПЛОХО: Прямая работа с $_GET (переедет в следующем уроке)
 * 3. ВСЁ ЕЩЁ ПЛОХО: N+1 запрос свойств в цикле (переедет в уроке про декомпозицию)
 */

// АНТИПАТТЕРН УДАЛЕН: CModule::IncludeModule("iblock"); - теперь это в классе

// ВСЁ ЕЩЁ ПЛОХО: Логика управления отображением через суперглобалы
if ($_GET["SHOW_FULL_LIST"] === "Y") {
    $arResult["SHOW_ALL"] = true;
}

foreach ($arResult["ITEMS"] as &$arItem) {
    // ВСЁ ЕЩЁ ПЛОХО: Запрос свойств в цикле (N+1)
    $dbProp = CIBlockElement::GetProperty(
        $arItem["IBLOCK_ID"],
        $arItem["ID"],
        array("sort" => "asc"),
        Array("CODE" => "SOURCE")
    );
    if ($arProp = $dbProp->Fetch()) {
        $arItem["SOURCE_VALUE"] = $arProp["VALUE"];
    }

    // ВСЁ ЕЩЁ ПЛОХО: Тяжелая обработка строк и HTML в модификаторе
    $arItem["NAME"] = mb_strtoupper($arItem["NAME"]);
    $arItem["FORMATTED_NAME"] = "<b>" . $arItem["NAME"] . "</b>";
}
unset($arItem);

// ВСЁ ЕЩЁ ПЛОХО: Побочные эффекты (изменение глобального состояния)
if ($arResult["TOTAL_COUNT"] > 5) {
    $GLOBALS["APPLICATION"]->SetTitle("Новости (" . $arResult["TOTAL_COUNT"] . ")");
}

// ВСЁ ЕЩЁ ПЛОХО: Лишние данные в кэше
$arResult["TIME_NOW"] = date("H:i:s");




