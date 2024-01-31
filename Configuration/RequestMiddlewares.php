<?php

return [
    'frontend' => [
        'rx/name' => [
            'target' => \CodingFreaks\CfCookiemanager\Middleware\ModifyHtmlContent::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ]
];