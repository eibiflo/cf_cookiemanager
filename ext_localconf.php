<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Configuration\SiteConfiguration;
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

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CfCookiemanager',
        'CookieList',
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'cookieList'
        ],
        // non-cacheable actions
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => ''
        ]
    );

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CfCookiemanager',
        'IframeManagerThumbnail',
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'thumbnail'
        ],
        // non-cacheable actions
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => ''
        ]
    );

})();


$versionInformation = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
if($versionInformation->getMajorVersion() <= 12){
    /* @deprecated  since v12. */
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1287112284] = [
        'nodeName' => 'CfSelectMultipleSideBySide',
        'priority' => '70',
        'class' => \CodingFreaks\CfCookiemanager\Form\Element\CfSelectMultipleSideBySideElement::class,
    ];
}else{
    /* Refactored MultipleSideBySide Element for Typo3 13 Style Standards */
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1287112284] = [
        'nodeName' => 'CfSelectMultipleSideBySide',
        'priority' => '70',
        'class' => \CodingFreaks\CfCookiemanager\Form\Element\CfSelectMultipleSideBySideElement13::class,
    ];

}

$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][] = "cf_thumbnail";

// Register css for backend Modal of API-changes and API-Updates
$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['cf_cookiemanager'] = 'EXT:cf_cookiemanager/Resources/Public/Backend/Css/BackendModal.css';
