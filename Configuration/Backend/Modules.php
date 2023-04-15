<?php

/**
 * Definitions for modules provided by EXT:cf_cookiemanager
 */

 return [
    'cookiesettings' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'admin',
        'workspaces' => 'live',
        'standalone' => true,
        'iconIdentifier' => 'cf_cookiemanager-plugin-cookiefrontend',
        'path' => '/module/web/CfCookiemanagerCookiesettings',
        'labels' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_cookiesettings.xlf',
        'extensionName' => 'CfCookiemanager',
        'controllerActions' => [
            CodingFreaks\CfCookiemanager\Controller\CookieSettingsBackendController::class => [
                'index',
            ],
        ],

    ],
];
