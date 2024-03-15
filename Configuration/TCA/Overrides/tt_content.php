<?php
defined('TYPO3') || die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'CfCookiemanager',
    'Cookiefrontend',
    'CookieFrontend'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'CfCookiemanager',
    'CookieList',
    'Cookie List for Data Policy Page'
);
