<?php

return [
    'autoload' => false,
    'hooks' => [
        'module_init' => [
            'qiniu',
        ],
        'upload_config_init' => [
            'qiniu',
        ],
        'upload_delete' => [
            'qiniu',
        ],
        'app_init' => [
            'qiniu',
        ],
        'config_init' => [
            'summernote',
        ],
    ],
    'route' => [],
    'priority' => [],
    'domain' => '',
];
