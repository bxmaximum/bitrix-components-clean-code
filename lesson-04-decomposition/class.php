<?php
/**
 * Урок 4: Декомпозиция
 *
 * ЧТО ИЗМЕНИЛОСЬ:
 * + Логика разбита на методы: getRawData(), formatItems(), etc.
 * + executeComponent() короткий
 * + D7 ORM (ElementNewsTable) вместо CIBlockElement::GetList
 * + N+1 исправлен — авторы одним запросом
 * + DETAIL_PAGE_URL: шаблон из IBLOCK + CIBlock::ReplaceDetailUrl
 * + Тег инфоблока явно: ORM, в отличие от CIBlockElement::GetList, его не регистрирует
 *
 * ТРЕБОВАНИЕ: у инфоблока API-код News → ElementNewsTable.
 * ID инфоблока знает сама сущность, параметр IBLOCK_ID компоненту не нужен.
 */

declare(strict_types=1);

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Iblock\Elements\ElementNewsTable;
use Bitrix\Main\UserTable;

class NewsListComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($params): array
    {
        $params['NEWS_COUNT'] = (int)($params['NEWS_COUNT'] ?? 10);
        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 3600);
        // CACHE_TYPE ядро уже привело к A, Y или N до вызова этого метода
        $params['CACHE_GROUPS'] = $params['CACHE_GROUPS'] ?? 'Y';

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

    protected function getRawData(): array
    {
        // ElementNewsTable привязан к инфоблоку с API-кодом News, условие по IBLOCK_ID добавит сам.
        // Шаблон детальной URL берём из связи IBLOCK, готовый URL — в formatItems().
        return ElementNewsTable::getList([
            'select' => [
                'ID',
                'NAME',
                'PREVIEW_TEXT',
                'PREVIEW_PICTURE',
                'ACTIVE_FROM',
                'CREATED_BY',
                'CODE',
                'IBLOCK_ID',
                'IBLOCK_SECTION_ID',
                'DETAIL_PAGE_URL' => 'IBLOCK.DETAIL_PAGE_URL',
            ],
            'filter' => [
                '=ACTIVE' => 'Y',
            ],
            'order' => [
                'ACTIVE_FROM' => $this->arParams['SORT_ORDER'],
                'ID' => 'DESC',
            ],
            'limit' => $this->arParams['NEWS_COUNT'],
        ])->fetchAll();
    }

    protected function loadAuthors(array $items): array
    {
        $authorIds = array_unique(array_filter(array_column($items, 'CREATED_BY')));

        if ($authorIds === []) {
            return [];
        }

        $result = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME'],
            'filter' => ['=ID' => $authorIds],
        ]);

        $authors = [];
        while ($user = $result->fetch()) {
            $authors[$user['ID']] = trim($user['NAME'] . ' ' . $user['LAST_NAME']);
        }

        return $authors;
    }

    protected function formatItems(array $items, array $authors): array
    {
        return array_map(function (array $item) use ($authors) {
            if (!empty($item['PREVIEW_PICTURE'])) {
                $item['PREVIEW_PICTURE_SRC'] = \CFile::GetPath($item['PREVIEW_PICTURE']);
            }

            if (!empty($item['ACTIVE_FROM'])) {
                $item['ACTIVE_FROM_FORMATTED'] = FormatDate(
                    'd F Y',
                    $item['ACTIVE_FROM']->getTimestamp()
                );
            }

            if (!empty($item['CREATED_BY']) && isset($authors[$item['CREATED_BY']])) {
                $item['AUTHOR_NAME'] = $authors[$item['CREATED_BY']];
            }

            $item['DETAIL_PAGE_URL'] = \CIBlock::ReplaceDetailUrl(
                (string)($item['DETAIL_PAGE_URL'] ?? ''),
                $item,
                false,
                'E'
            );

            return $item;
        }, $items);
    }

    protected function setSeo(): void
    {
        global $APPLICATION;

        $APPLICATION->SetTitle('Новости компании');
        $APPLICATION->SetPageProperty('description', 'Последние новости нашей компании');
    }

    public function executeComponent(): void
    {
        try {
            $this->checkModules();

            if ($this->startResultCache($this->arParams['CACHE_TIME'], $this->getAdditionalCacheId())) {
                \CIBlock::registerWithTagCache(ElementNewsTable::getEntity()->getIblock()->getId());

                $rawItems = $this->getRawData();

                $items = $this->formatItems($rawItems, $this->loadAuthors($rawItems));

                $this->arResult = [
                    'ITEMS' => $items,
                    'TOTAL_COUNT' => count($items),
                    'IS_ADMIN' => CurrentUser::get()->isAdmin(),
                ];

                $this->setResultCacheKeys(['ITEMS', 'TOTAL_COUNT', 'IS_ADMIN']);

                $this->includeComponentTemplate();
            }

            $this->setSeo();
        } catch (\Exception $e) {
            $this->abortResultCache();
            ShowError($e->getMessage());
        }
    }
}
