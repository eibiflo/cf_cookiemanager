<?php

return [
    'frontend' => [
        'codingfreaks/cf-cookiemanager/gdprhook' => [
            'target' => \CodingFreaks\CfCookiemanager\Middleware\ModifyHtmlContent::class,
            'after' => [
                'typo3/cms-frontend/content-length-headers'
            ],
        ],
    ]
];