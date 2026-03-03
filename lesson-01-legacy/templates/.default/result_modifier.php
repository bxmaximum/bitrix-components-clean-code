<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * АНТИПАТТЕРНЫ В ЭТОМ ФАЙЛЕ:
 * 1. Запросы к БД в цикле (N+1)
 * 2. Использование старого API
 * 3. Изменение глобальных SEO-свойств страницы
 * 4. Прямое обращение к суперглобальным массивам ($_GET)
 * 5. Тяжелые вычисления, которые не кэшируются (если компонент не кэшируется)
 * 6. Изменение структуры arResult "на лету"
 */

// АНТИПАТТЕРН: Подключение модуля в модификаторе
CModule::IncludeModule("iblock");

// АНТИПАТТЕРН: Прямая работа с $_GET
if ($_GET["SHOW_FULL_LIST"] === "Y") {
    $arResult["SHOW_ALL"] = true;
}

foreach ($arResult["ITEMS"] as &$arItem) {
    // АНТИПАТТЕРН: Запрос свойств в цикле (N+1)
    // Хотя в component.php уже был GetProperties(), мы сделаем это еще раз по-другому
    $dbProp = CIBlockElement::GetProperty(
        $arItem["IBLOCK_ID"],
        $arItem["ID"],
        array("sort" => "asc"),
        Array("CODE" => "SOURCE")
    );
    if ($arProp = $dbProp->Fetch()) {
        $arItem["SOURCE_VALUE"] = $arProp["VALUE"];
    }

    // АНТИПАТТЕРН: Тяжелая обработка строк в цикле
    $arItem["NAME"] = mb_strtoupper($arItem["NAME"]);
    
    // АНТИПАТТЕРН: Формирование HTML прямо в коде
    $arItem["FORMATTED_NAME"] = "<b>" . $arItem["NAME"] . "</b>";
}
unset($arItem);

// АНТИПАТТЕРН: Побочные эффекты - изменение заголовка страницы из модификатора шаблона
if ($arResult["TOTAL_COUNT"] > 5) {
    $GLOBALS["APPLICATION"]->SetTitle("Очень много новостей (" . $arResult["TOTAL_COUNT"] . ")");
}

// АНТИПАТТЕРН: Создание лишних ключей в arResult, которые могут раздуть кэш
$arResult["TIME_NOW"] = date("H:i:s");

