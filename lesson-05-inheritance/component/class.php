<?php
/**
 * Урок 5: Наследование и трейты
 */

declare(strict_types=1);

namespace Project\Components;

use Bitrix\Main\Loader;
use Bitrix\Iblock\Elements\ElementNewsTable;
use Bitrix\Main\UserTable;
use Project\Core\Component\BaseComponent;
use Project\Core\Traits\WithSeo;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

Loader::includeModule('project.core');

class NewsListComponent extends BaseComponent
{
    use WithSeo;

    public function onPrepareComponentParams($params): array
    {
        $params = parent::onPrepareComponentParams($params);

        $params['NEWS_COUNT'] = min(max((int)($params['NEWS_COUNT'] ?? 10), 1), 100);
        $params['SORT_ORDER'] = in_array($params['SORT_ORDER'] ?? '', ['ASC', 'DESC'], true)
            ? $params['SORT_ORDER']
            : 'DESC';

        return $params;
    }

    protected function getRawData(): array
    {
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
            'filter' => ['=ACTIVE' => 'Y'],
            'order' => ['ACTIVE_FROM' => $this->arParams['SORT_ORDER'], 'ID' => 'DESC'],
            'limit' => $this->arParams['NEWS_COUNT'],
        ])->fetchAll();
    }

    protected function loadAuthors(array $items): array
    {
        $authorIds = array_unique(array_filter(array_column($items, 'CREATED_BY')));
        if ($authorIds === []) {
            return [];
        }

        $authors = [];
        $result = UserTable::getList([
            'select' => ['ID', 'NAME', 'LAST_NAME'],
            'filter' => ['=ID' => $authorIds],
        ]);
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
                $item['ACTIVE_FROM_FORMATTED'] = FormatDate('d F Y', $item['ACTIVE_FROM']->getTimestamp());
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

    public function executeComponent(): void
    {
        try {
            $this->checkModules(['iblock']);

            if ($this->startResultCache($this->arParams['CACHE_TIME'], $this->getAdditionalCacheId())) {
                \CIBlock::registerWithTagCache(ElementNewsTable::getEntity()->getIblock()->getId());

                $rawItems = $this->getRawData();
                $items = $this->formatItems($rawItems, $this->loadAuthors($rawItems));

                $this->arResult = [
                    'ITEMS' => $items,
                    'TOTAL_COUNT' => count($items),
                    'IS_ADMIN' => $this->isAdmin(),
                ];

                $this->setResultCacheKeys(['ITEMS', 'TOTAL_COUNT', 'IS_ADMIN']);
                $this->includeComponentTemplate();
            }

            $this->setSeo('Новости компании', 'Последние новости нашей компании');
            $this->addBreadcrumb('Новости', '/news/');
        } catch (\Exception $e) {
            $this->abort($e->getMessage());
        }
    }
}
