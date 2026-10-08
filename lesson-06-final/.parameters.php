<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arComponentParameters = [
    'GROUPS' => [],
    'PARAMETERS' => [
        'NEWS_COUNT' => [
            'PARENT' => 'BASE',
            'NAME' => 'Количество элементов',
            'TYPE' => 'STRING',
            'DEFAULT' => '10',
        ],
        'SORT_ORDER' => [
            'PARENT' => 'BASE',
            'NAME' => 'Направление сортировки по дате',
            'TYPE' => 'LIST',
            'VALUES' => [
                'DESC' => 'DESC',
                'ASC' => 'ASC',
            ],
            'DEFAULT' => 'DESC',
        ],
        // Ядро достроит группу «Настройки кеширования» и поле CACHE_TYPE само
        'CACHE_TIME' => ['DEFAULT' => 3600],
        'CACHE_GROUPS' => [
            'PARENT' => 'CACHE_SETTINGS',
            'NAME' => 'Учитывать права доступа',
            'TYPE' => 'CHECKBOX',
            'DEFAULT' => 'Y',
        ],
    ],
];
