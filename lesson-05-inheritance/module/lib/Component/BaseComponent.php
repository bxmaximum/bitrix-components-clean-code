<?php
/**
 * Базовый класс для всех компонентов проекта
 *
 * Файл: /local/modules/project.core/lib/Component/BaseComponent.php
 *
 * Важно: не называйте метод getCacheId() — в PHP имена методов
 * case-insensitive, а у CBitrixComponent уже есть public getCacheID().
 */

declare(strict_types=1);

namespace Project\Core\Component;

use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Bitrix\Main\Engine\CurrentUser;

abstract class BaseComponent extends \CBitrixComponent
{
    protected function checkModules(array $modules): void
    {
        foreach ($modules as $module) {
            if (!Loader::includeModule($module)) {
                throw new SystemException("Модуль {$module} не установлен");
            }
        }
    }

    protected function abort(string $message): void
    {
        $this->abortResultCache();
        ShowError($message);
    }

    public function onPrepareComponentParams($params): array
    {
        $params['CACHE_TIME'] = (int)($params['CACHE_TIME'] ?? 3600);
        // CACHE_TYPE ядро уже привело к A, Y или N до вызова этого метода
        $params['CACHE_GROUPS'] = $params['CACHE_GROUPS'] ?? 'Y';

        return $params;
    }

    /**
     * Доп. факторы для 2-го аргумента startResultCache($cacheTime, $additionalCacheID).
     * Не путать с ядром CBitrixComponent::getCacheID().
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

    protected function isAdmin(): bool
    {
        return CurrentUser::get()->isAdmin();
    }
}
