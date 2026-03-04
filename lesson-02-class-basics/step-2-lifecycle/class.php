<?php
/**
 * Урок 2: Первый шаг к ООП
 * 
 * Шаг 2: Использование жизненного цикла (Lifecycle)
 * Мы разнесли логику по методам onPrepareComponentParams и checkModules,
 * а также внедрили обработку ошибок через try/catch.
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;

class NewsListComponent extends CBitrixComponent
{
    /**
     * Валидация параметров - теперь в отдельном методе!
     * Вызывается автоматически ДО кэширования.
     */
    public function onPrepareComponentParams($params): array
    {
        // Приводим к типам
        $params["IBLOCK_ID"] = (int)($params["IBLOCK_ID"] ?? 0);
        $params["NEWS_COUNT"] = (int)($params["NEWS_COUNT"] ?? 10);
        $params["CACHE_TIME"] = (int)($params["CACHE_TIME"] ?? 3600);

        return $params;
    }

    /**
     * Проверка подключения модулей
     */
    protected function checkModules(): void
    {
        if (!Loader::includeModule("iblock")) {
            throw new \Bitrix\Main\SystemException("Модуль iblock не установлен");
        }
    }

    /**
     * Точка входа
     * ПРОБЛЕМА: Метод всё ещё слишком большой!
     */
    public function executeComponent()
    {
        try {
            $this->checkModules();

            // Используем встроенное кэширование вместо CPHPCache
            if ($this->startResultCache()) {

                $this->arResult["ITEMS"] = [];

                // Всё ещё старый API, но хотя бы в классе
                $arFilter = [
                    "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
                    "ACTIVE" => "Y"
                ];

                $arOrder = ["ACTIVE_FROM" => "DESC", "ID" => "DESC"];

                $rsElements = \CIBlockElement::GetList(
                    $arOrder,
                    $arFilter,
                    false,
                    ["nTopCount" => $this->arParams["NEWS_COUNT"]],
                    ["ID", "NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "ACTIVE_FROM", "CREATED_BY"]
                );

                while ($obElement = $rsElements->GetNextElement()) {
                    $arItem = $obElement->GetFields();
                    $arItem["PROPERTIES"] = $obElement->GetProperties();

                    // Форматирование всё ещё здесь (плохо!)
                    if ($arItem["PREVIEW_PICTURE"]) {
                        $arItem["PREVIEW_PICTURE_SRC"] = \CFile::GetPath($arItem["PREVIEW_PICTURE"]);
                    }

                    if ($arItem["ACTIVE_FROM"]) {
                        $arItem["ACTIVE_FROM_FORMATTED"] = FormatDate("d F Y", MakeTimeStamp($arItem["ACTIVE_FROM"]));
                    }

                    // N+1 всё ещё здесь (плохо!)
                    if ($arItem["CREATED_BY"]) {
                        $rsUser = \CUser::GetByID($arItem["CREATED_BY"]);
                        if ($arUser = $rsUser->Fetch()) {
                            $arItem["AUTHOR_NAME"] = $arUser["NAME"] . " " . $arUser["LAST_NAME"];
                        }
                    }

                    // Проверка прав (антипаттерн в цикле)
                    if ($GLOBALS["USER"]->IsAdmin()) {
                        $arItem["CAN_EDIT"] = true;
                        $arItem["EDIT_LINK"] = "/bitrix/admin/iblock_element_edit.php?ID=" . $arItem["ID"] . "&IBLOCK_ID=" . $this->arParams["IBLOCK_ID"];
                    }

                    $this->arResult["ITEMS"][] = $arItem;
                }

                $this->arResult["TOTAL_COUNT"] = count($this->arResult["ITEMS"]);

                if (empty($this->arResult["ITEMS"])) {
                    $this->arResult["ERROR_MESSAGE"] = "Новости не найдены";
                    $this->abortResultCache();
                }

                $this->includeComponentTemplate();
            }

        } catch (\Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }

        // SEO и заголовки
        $GLOBALS["APPLICATION"]->SetTitle("Новости компании");
        $GLOBALS["APPLICATION"]->SetPageProperty("description", "Последние новости нашей компании");
        $GLOBALS["APPLICATION"]->AddChainItem("Новости", "/news/");
    }
}
