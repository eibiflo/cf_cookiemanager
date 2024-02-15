<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CfCookiemanager',
        'Cookiefrontend',
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'list,track'
        ],
        // non-cacheable actions
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'track'
        ]
    );

})();


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['CfCookiemanager_staticdataUpdateWizard']
    = \CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard::class;

/* Add new field type to NodeFactory */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1287112284] = [
    'nodeName' => 'CfSelectMultipleSideBySide',
    'priority' => '70',
    'class' => \CodingFreaks\CfCookiemanager\Form\Element\CfSelectMultipleSideBySideElement::class,
];