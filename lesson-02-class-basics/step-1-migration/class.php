<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

/**
 * Шаг 1: Миграция "в лоб"
 * Мы просто перенесли код из component.php в executeComponent,
 * заменив процедурные вызовы на методы объекта.
 */
class NewsListComponent extends CBitrixComponent
{
    public function executeComponent()
    {   
        // $arParams -> $this->arParams
        $this->arParams["IBLOCK_ID"] = intval($this->arParams["IBLOCK_ID"]);
        $this->arParams["NEWS_COUNT"] = $this->arParams["NEWS_COUNT"] ? intval($this->arParams["NEWS_COUNT"]) : 10;
        $this->arParams["CACHE_TIME"] = $this->arParams["CACHE_TIME"] ? intval($this->arParams["CACHE_TIME"]) : 3600;

        \Bitrix\Main\Loader::includeModule("iblock");

        // Вместо CPHPCache используем встроенный startResultCache
        if ($this->startResultCache()) {
            
            $this->arResult = array();
            $this->arResult["ITEMS"] = array();

            $rsElements = \CIBlockElement::GetList(
                array("ACTIVE_FROM" => "DESC", "ID" => "DESC"),
                array("IBLOCK_ID" => $this->arParams["IBLOCK_ID"], "ACTIVE" => "Y"),
                false,
                array("nTopCount" => $this->arParams["NEWS_COUNT"]),
                array("ID", "NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "ACTIVE_FROM", "CREATED_BY")
            );

            while ($obElement = $rsElements->GetNextElement()) {
                $arItem = $obElement->GetFields();
                $arItem["PROPERTIES"] = $obElement->GetProperties();
                
                if ($arItem["PREVIEW_PICTURE"]) {
                    $arItem["PREVIEW_PICTURE_SRC"] = \CFile::GetPath($arItem["PREVIEW_PICTURE"]);
                }

                if ($arItem["ACTIVE_FROM"]) {
                    $arItem["ACTIVE_FROM_FORMATTED"] = FormatDate("d F Y", MakeTimeStamp($arItem["ACTIVE_FROM"]));
                }

                // N+1 всё еще здесь
                if ($arItem["CREATED_BY"]) {
                    $rsUser = \CUser::GetByID($arItem["CREATED_BY"]);
                    if ($arUser = $rsUser->Fetch()) {
                        $arItem["AUTHOR_NAME"] = $arUser["NAME"] . " " . $arUser["LAST_NAME"];
                    }
                }

                // Проверка прав прямо в цикле (антипаттерн)
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

        // SEO и заголовки (пока в куче)
        $GLOBALS["APPLICATION"]->SetTitle("Новости компании");
        $GLOBALS["APPLICATION"]->SetPageProperty("description", "Последние новости нашей компании");
        $GLOBALS["APPLICATION"]->AddChainItem("Новости", "/news/");
    }
}
