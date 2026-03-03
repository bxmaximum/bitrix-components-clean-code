<?php
/**
 * Компонент "Список новостей" - типичный legacy-код 2015 года
 *
 * АНТИПАТТЕРНЫ В ЭТОМ ФАЙЛЕ:
 * 1. Вся логика в одном процедурном файле
 * 2. Использование глобальных переменных ($USER, $APPLICATION)
 * 3. Старый API (CIBlockElement::GetList)
 * 4. Нет валидации параметров
 * 5. Кэширование без учета групп пользователей
 * 6. Запросы внутри циклов (N+1)
 * 7. Логика форматирования смешана с получением данных
 * 8. Отсутствие обработки ошибок
 * 9. Магические числа и строки
 * 10. Отсутствие типизации
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

// АНТИПАТТЕРН: Глобальные переменные
global $USER, $APPLICATION;

// АНТИПАТТЕРН: Нет валидации, просто приведение к int прямо здесь
$arParams["IBLOCK_ID"] = intval($arParams["IBLOCK_ID"]);
$arParams["NEWS_COUNT"] = $arParams["NEWS_COUNT"] ? intval($arParams["NEWS_COUNT"]) : 10;
$arParams["CACHE_TIME"] = $arParams["CACHE_TIME"] ? intval($arParams["CACHE_TIME"]) : 3600;

// АНТИПАТТЕРН: Проверка модуля без обработки ошибки
CModule::IncludeModule("iblock");

// АНТИПАТТЕРН: Ручное кэширование без additionalCacheId
// Если данные зависят от пользователя - будет Cache Poisoning!
$obCache = new CPHPCache();
$cacheTime = $arParams["CACHE_TIME"];
$cacheId = serialize($arParams);
$cachePath = "/" . SITE_ID . "/news_list/";

if ($obCache->InitCache($cacheTime, $cacheId, $cachePath)) {
    $vars = $obCache->GetVars();
    $arResult = $vars["arResult"];
} else {
    $obCache->StartDataCache();

    $arResult = array();
    $arResult["ITEMS"] = array();

    // АНТИПАТТЕРН: Старый API вместо D7 ORM
    // АНТИПАТТЕРН: Выбираем ВСЕ поля вместо нужных
    $arFilter = array(
        "IBLOCK_ID" => $arParams["IBLOCK_ID"],
        "ACTIVE" => "Y"
    );

    // АНТИПАТТЕРН: Магическая сортировка без параметра
    $arOrder = array("ACTIVE_FROM" => "DESC", "ID" => "DESC");

    $rsElements = CIBlockElement::GetList(
        $arOrder,
        $arFilter,
        false,
        array("nTopCount" => $arParams["NEWS_COUNT"]),
        array("ID", "NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "ACTIVE_FROM", "CREATED_BY")
    );

    while ($obElement = $rsElements->GetNextElement()) {
        $arItem = $obElement->GetFields();
        $arItem["PROPERTIES"] = $obElement->GetProperties();

        // АНТИПАТТЕРН: Форматирование прямо в цикле получения данных
        if ($arItem["PREVIEW_PICTURE"]) {
            $arItem["PREVIEW_PICTURE_SRC"] = CFile::GetPath($arItem["PREVIEW_PICTURE"]);
        }

        // АНТИПАТТЕРН: Форматирование даты здесь же
        if ($arItem["ACTIVE_FROM"]) {
            $arItem["ACTIVE_FROM_FORMATTED"] = FormatDate("d F Y", MakeTimeStamp($arItem["ACTIVE_FROM"]));
        }

        // АНТИПАТТЕРН: N+1 запрос - получаем автора для КАЖДОГО элемента отдельным запросом!
        if ($arItem["CREATED_BY"]) {
            $rsUser = CUser::GetByID($arItem["CREATED_BY"]);
            if ($arUser = $rsUser->Fetch()) {
                $arItem["AUTHOR_NAME"] = $arUser["NAME"] . " " . $arUser["LAST_NAME"];
            }
        }

        // АНТИПАТТЕРН: Проверка прав прямо в цикле
        if ($USER->IsAdmin()) {
            $arItem["CAN_EDIT"] = true;
            $arItem["EDIT_LINK"] = "/bitrix/admin/iblock_element_edit.php?ID=" . $arItem["ID"] . "&IBLOCK_ID=" . $arParams["IBLOCK_ID"];
        }

        $arResult["ITEMS"][] = $arItem;
    }

    // АНТИПАТТЕРН: Подсчет без использования CNT
    $arResult["TOTAL_COUNT"] = count($arResult["ITEMS"]);

    // АНТИПАТТЕРН: Логика для пустого результата
    if (empty($arResult["ITEMS"])) {
        $arResult["ERROR_MESSAGE"] = "Новости не найдены";
    }

    $obCache->EndDataCache(array("arResult" => $arResult));
}

// АНТИПАТТЕРН: SEO внутри компонента без проверки
$APPLICATION->SetTitle("Новости компании");
$APPLICATION->SetPageProperty("description", "Последние новости нашей компании");

// АНТИПАТТЕРН: Хлебные крошки захардкожены
$APPLICATION->AddChainItem("Новости", "/news/");

$this->IncludeComponentTemplate();
