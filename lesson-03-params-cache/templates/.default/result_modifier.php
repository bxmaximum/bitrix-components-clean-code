<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * ЧТО ИЗМЕНИЛОСЬ В УРОКЕ 3:
 * 1. ИСПРАВЛЕНО: Работа с $_GET переехала в onPrepareComponentParams()
 * 2. ИСПРАВЛЕНО: Лишние ключи (TIME_NOW) удалены, так как они портят кэш
 * 3. ВСЁ ЕЩЁ ПЛОХО: N+1 запрос свойств в цикле (исправим в уроке 4)
 */

// ИСПРАВЛЕНО: Логика SHOW_FULL_LIST теперь в onPrepareComponentParams через $arParams
if ($arParams["SHOW_FULL_LIST"] === "Y") {
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

    $arItem["NAME"] = mb_strtoupper($arItem["NAME"]);
    $arItem["FORMATTED_NAME"] = "<b>" . $arItem["NAME"] . "</b>";
}
unset($arItem);

// ВСЁ ЕЩЁ ПЛОХО: Побочные эффекты (переедет в метод setSeo в уроке 4)
if ($arResult["TOTAL_COUNT"] > 5) {
    $GLOBALS["APPLICATION"]->SetTitle("Новости (" . $arResult["TOTAL_COUNT"] . ")");
}




