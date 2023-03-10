<?php
defined('TYPO3') || die();

(static function() {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'CfCookiemanager',
        'Cookiefrontend',
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'list'
        ],
        // non-cacheable actions
        [
            \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'list'
        ]
    );

    // wizards
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
})();
## EXTENSION BUILDER DEFAULTS END TOKEN - Everything BEFORE this line is overwritten with the defaults of the extension builder

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['CfCookiemanager_staticdataUpdateWizard']
    = \CodingFreaks\CfCookiemanager\Updates\StaticDataUpdateWizard::class;

$GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['FE']['ContentObjects'], [
    'CONTENT'          => \CodingFreaks\CfCookiemanager\Hooks\ContentObjectRendererHook::class,
]);


$pageRenderer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
$pageRenderer->addRequireJsConfiguration(
    [
        'paths' => [
            'jqueryDatatable' => TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath(
                'EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/DataTable.min'),
        ],
        'shim' => [
            'deps' => ['jquery'],
            'jqueryDatatable' => ['exports' => 'jqueryDatatable'],
        ],
    ]
);
$pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/CfCookiemanagerIndex');



/* Add new field type to NodeFactory */
$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1287112284] = [
    'nodeName' => 'CfSelectMultipleSideBySide',
    'priority' => '70',
    'class' => \CodingFreaks\CfCookiemanager\Form\Element\CfSelectMultipleSideBySideElement::class,
];
