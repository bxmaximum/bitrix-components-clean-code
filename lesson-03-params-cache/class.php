<?php
/**
 * Урок 3: Параметры и кэширование
 *
 * ЧТО ИЗМЕНИЛОСЬ:
 * + Полноценная валидация в onPrepareComponentParams (без throw — ядро
 *   вызывает prepare до executeComponent, catch там не поймает)
 * + additionalCacheID для защиты от Cache Poisoning
 * + setResultCacheKeys — какие ключи $arResult оставить в файле кэша
 * + CACHE_GROUPS: группы пользователя в ключе кэша
 * + IS_ADMIN ставится ДО шаблона (разметка зависит от роли → роль в ключе кэша)
 *
 * ЧТО ЕЩЁ ПЛОХО:
 * - Старый API (CIBlockElement)
 * - N+1 запросы
 * - Нет декомпозиции
 */

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Application;

class NewsListComponent extends CBitrixComponent
{
    /**
     * Нормализация контракта. Не бросаем исключения здесь:
     * onPrepareComponentParams вызывается ядром до executeComponent.
     */
    public function onPrepareComponentParams($params): array
    {
        $request = Application::getInstance()->getContext()->getRequest();

        $params['IBLOCK_ID'] = (int)($params['IBLOCK_ID'] ?? 0);
        $params['NEWS_COUNT'] = (int)($params['NEWS_COUNT'] ?? 10);
        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 3600);
        // CACHE_TYPE ядро уже привело к A, Y или N до вызова этого метода
        $params['CACHE_GROUPS'] = $params['CACHE_GROUPS'] ?? 'Y';
        $params['SHOW_FULL_LIST'] = ($request->get('SHOW_FULL_LIST') === 'Y') ? 'Y' : 'N';

        $params['SORT_ORDER'] = in_array($params['SORT_ORDER'] ?? '', ['ASC', 'DESC'], true)
            ? $params['SORT_ORDER']
            : 'DESC';

        if ($params['NEWS_COUNT'] > 100) {
            $params['NEWS_COUNT'] = 100;
        }

        if ($params['NEWS_COUNT'] < 1) {
            $params['NEWS_COUNT'] = 10;
        }

        return $params;
    }

    protected function checkModules(): void
    {
        if (!Loader::includeModule('iblock')) {
            throw new \Bitrix\Main\SystemException('Модуль iblock не установлен');
        }
    }

    /**
     * 2-й аргумент startResultCache — additionalCacheID (имя в ядре).
     */
    protected function getAdditionalCacheId(): array
    {
        $currentUser = CurrentUser::get();
        $id = [
            'is_authorized' => $currentUser->getId() > 0,
        ];

        if ($this->arParams['CACHE_GROUPS'] !== 'N') {
            $id['user_groups'] = $currentUser->getUserGroups();
        }

        return $id;
    }

    public function executeComponent(): void
    {
        try {
            if ($this->arParams['IBLOCK_ID'] <= 0) {
                ShowError('Укажите корректный IBLOCK_ID');
                return;
            }

            $this->checkModules();

            if ($this->startResultCache($this->arParams['CACHE_TIME'], $this->getAdditionalCacheId())) {
                $this->arResult['ITEMS'] = [];

                $arFilter = [
                    'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
                    'ACTIVE' => 'Y',
                ];

                $arOrder = [
                    'ACTIVE_FROM' => $this->arParams['SORT_ORDER'],
                    'ID' => 'DESC',
                ];

                $rsElements = \CIBlockElement::GetList(
                    $arOrder,
                    $arFilter,
                    false,
                    ['nTopCount' => $this->arParams['NEWS_COUNT']],
                    ['ID', 'NAME', 'PREVIEW_TEXT', 'PREVIEW_PICTURE', 'DETAIL_PAGE_URL', 'ACTIVE_FROM', 'CREATED_BY']
                );

                while ($obElement = $rsElements->GetNextElement()) {
                    $arItem = $obElement->GetFields();

                    if ($arItem['PREVIEW_PICTURE']) {
                        $arItem['PREVIEW_PICTURE_SRC'] = \CFile::GetPath($arItem['PREVIEW_PICTURE']);
                    }

                    if ($arItem['ACTIVE_FROM']) {
                        $arItem['ACTIVE_FROM_FORMATTED'] = FormatDate('d F Y', MakeTimeStamp($arItem['ACTIVE_FROM']));
                    }

                    // N+1 всё ещё здесь — исправим в уроке 4
                    if ($arItem['CREATED_BY']) {
                        $rsUser = \CUser::GetByID($arItem['CREATED_BY']);
                        if ($arUser = $rsUser->Fetch()) {
                            $arItem['AUTHOR_NAME'] = $arUser['NAME'] . ' ' . $arUser['LAST_NAME'];
                        }
                    }

                    $this->arResult['ITEMS'][] = $arItem;
                }

                $this->arResult['TOTAL_COUNT'] = count($this->arResult['ITEMS']);

                // Разметка шаблона зависит от роли → флаг ДО includeComponentTemplate,
                // а группы уже в additionalCacheID (см. getAdditionalCacheId).
                $this->arResult['IS_ADMIN'] = CurrentUser::get()->isAdmin();

                // Какие ключи $arResult оставить в файле кэша (для epilog / следующих хитов)
                $this->setResultCacheKeys(['ITEMS', 'TOTAL_COUNT', 'IS_ADMIN']);

                $this->includeComponentTemplate();
            }

            // Вне блока кэша — только то, что НЕ входит в HTML шаблона
            // (title, page properties). На cache hit шаблон.php не переисполняется.
            $GLOBALS['APPLICATION']->SetTitle('Новости компании');
        } catch (\Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }
}
