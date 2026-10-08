<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class project_core extends CModule
{
    public $MODULE_ID = 'project.core';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME = 'Базовые классы проекта';
    public $MODULE_DESCRIPTION = 'Базовые компоненты, трейты и хелперы';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall(): void
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}
