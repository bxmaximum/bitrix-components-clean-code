<?php
/**
 * Урок 3: Параметры и кэширование
 *
 * ЧТО ИЗМЕНИЛОСЬ:
 * + Полноценная валидация в onPrepareComponentParams
 * + additionalCacheId для защиты от Cache Poisoning
 * + setResultCacheKeys для выборочного кэширования
 * + Поддержка CACHE_TYPE
 *
 * ЧТО ЕЩЁ ПЛОХО:
 * - Старый API (CIBlockElement)
 * - N+1 запросы
 * - Нет декомпозиции
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\Application;

class NewsListComponent extends CBitrixComponent
{
    /**
     * Полноценная валидация параметров
     */
    public function onPrepareComponentParams($params): array
    {
        $request = Application::getInstance()->getContext()->getRequest();

        // 1. Приведение к типам
        $params["IBLOCK_ID"] = (int)($params["IBLOCK_ID"] ?? 0);
        $params["NEWS_COUNT"] = (int)($params["NEWS_COUNT"] ?? 10);
        $params["CACHE_TIME"] = (int)($params["CACHE_TIME"] ?? 3600);
        $params["CACHE_TYPE"] = $params["CACHE_TYPE"] ?? "A";
        $params["SHOW_FULL_LIST"] = ($request->get("SHOW_FULL_LIST") === "Y") ? "Y" : "N";

        // 2. Валидация критичных параметров
        if ($params["IBLOCK_ID"] <= 0) {
            throw new ArgumentException("IBLOCK_ID должен быть положительным числом");
        }

        // 3. Ограничение допустимых значений
        $params["SORT_ORDER"] = in_array($params["SORT_ORDER"] ?? "", ["ASC", "DESC"])
            ? $params["SORT_ORDER"]
            : "DESC";

        $params["CACHE_TYPE"] = in_array($params["CACHE_TYPE"], ["A", "Y", "N"])
            ? $params["CACHE_TYPE"]
            : "A";

        // 4. Разумные лимиты
        if ($params["NEWS_COUNT"] > 100) {
            $params["NEWS_COUNT"] = 100;
        }

        return $params;
    }

    protected function checkModules(): void
    {
        if (!Loader::includeModule("iblock")) {
            throw new \Bitrix\Main\SystemException("Модуль iblock не установлен");
        }
    }

    /**
     * Формируем дополнительный ключ кэша
     * Защита от Cache Poisoning
     */
    protected function getAdditionalCacheId(): array
    {
        $currentUser = CurrentUser::get();

        return [
            "user_groups" => $currentUser->getUserGroups(),
            "is_authorized" => $currentUser->getId() > 0,
        ];
    }

    /**
     * Проверяем, нужно ли кэширование
     */
    protected function shouldCache(): bool
    {
        return $this->arParams["CACHE_TYPE"] !== "N";
    }

    public function executeComponent()
    {
        try {
            $this->checkModules();

            $shouldCache = $this->shouldCache();
            $cacheId = $this->getAdditionalCacheId();

            // Кэширование с защитой от Cache Poisoning
            if (!$shouldCache || $this->startResultCache($this->arParams["CACHE_TIME"], $cacheId)) {

                $this->arResult["ITEMS"] = [];

                $arFilter = [
                    "IBLOCK_ID" => $this->arParams["IBLOCK_ID"],
                    "ACTIVE" => "Y"
                ];

                $arOrder = [
                    "ACTIVE_FROM" => $this->arParams["SORT_ORDER"],
                    "ID" => "DESC"
                ];

                $rsElements = \CIBlockElement::GetList(
                    $arOrder,
                    $arFilter,
                    false,
                    ["nTopCount" => $this->arParams["NEWS_COUNT"]],
                    ["ID", "NAME", "PREVIEW_TEXT", "PREVIEW_PICTURE", "DETAIL_PAGE_URL", "ACTIVE_FROM", "CREATED_BY"]
                );

                while ($obElement = $rsElements->GetNextElement()) {
                    $arItem = $obElement->GetFields();

                    if ($arItem["PREVIEW_PICTURE"]) {
                        $arItem["PREVIEW_PICTURE_SRC"] = \CFile::GetPath($arItem["PREVIEW_PICTURE"]);
                    }

                    if ($arItem["ACTIVE_FROM"]) {
                        $arItem["ACTIVE_FROM_FORMATTED"] = FormatDate("d F Y", MakeTimeStamp($arItem["ACTIVE_FROM"]));
                    }

                    // N+1 всё ещё здесь - исправим в уроке 4
                    if ($arItem["CREATED_BY"]) {
                        $rsUser = \CUser::GetByID($arItem["CREATED_BY"]);
                        if ($arUser = $rsUser->Fetch()) {
                            $arItem["AUTHOR_NAME"] = $arUser["NAME"] . " " . $arUser["LAST_NAME"];
                        }
                    }

                    $this->arResult["ITEMS"][] = $arItem;
                }

                $this->arResult["TOTAL_COUNT"] = count($this->arResult["ITEMS"]);

                if ($shouldCache) {
                    // Кэшируем только данные, не персонализацию
                    $this->setResultCacheKeys(["ITEMS", "TOTAL_COUNT"]);

                    if (empty($this->arResult["ITEMS"])) {
                        $this->abortResultCache();
                    }
                }

                $this->includeComponentTemplate();
            }

            // Данные вне кэша - персонализация
            $this->setPersonalData();

        } catch (ArgumentException $e) {
            ShowError("Ошибка параметров: " . $e->getMessage());
        } catch (\Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }

    /**
     * Персональные данные - НЕ кэшируются
     */
    protected function setPersonalData(): void
    {
        $currentUser = CurrentUser::get();
        $this->arResult["IS_ADMIN"] = in_array(1, $currentUser->getUserGroups());
    }
}
