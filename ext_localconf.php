<?php
defined('TYPO3') || die();
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

    /*
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'mod {
            wizards.newContentElement.wizardItems.plugins {
                elements {
                    cookiefrontend {
                        iconIdentifier = cf_cookiemanager-plugin-cookiefrontend
                        title = LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cf_cookiemanager_cookiefrontend.name
                        description = LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cf_cookiemanager_cookiefrontend.description
                        tt_content_defValues {
                            CType = list
                            list_type = cfcookiemanager_cookiefrontend
                        }
                    }
                }
                show = *
            }
       }'
    );
    */
})();


// Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
$versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
if ($versionInformation->getMajorVersion() < 12) {

    $GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'], [
        'CONTENT'  => \CodingFreaks\CfCookiemanager\Hooks\ContentObjectRendererHook::class,
    ]);

    ExtensionManagementUtility::addPageTSConfig('
      @import "EXT:cf_cookiemanager/Configuration/page.tsconfig"
   ');
}


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['CfCookiemanager_staticdataUpdateWizard']
    = \CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard::class;

/* Add new field type to NodeFactory */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1287112284] = [
    'nodeName' => 'CfSelectMultipleSideBySide',
    'priority' => '70',
    'class' => \CodingFreaks\CfCookiemanager\Form\Element\CfSelectMultipleSideBySideElement::class,
];
