<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function() {

    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'CfCookiemanager',
        'web',
        'cookiesettings',
        '',
        [
            //  \CodingFreaks\CfCookiemanager\Controller\CookieCartegoriesController::class => 'list',\CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class => 'list',
            \CodingFreaks\CfCookiemanager\Controller\CookieSettingsBackendController::class => 'index',\CodingFreaks\CfCookiemanager\Controller\CookieSettingsBackendController::class => 'index',
        ],
        [
            'access' => 'user,group',
            'icon'   => 'EXT:cf_cookiemanager/Resources/Public/Icons/user_mod_cookiesettings.svg',
            'labels' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_cookiesettings.xlf',
        ]
    );

    $GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["cookie_services"]["config"]["multiSelectFilterItems"] = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getCookieServicesMultiSelectFilterItems();
    $GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["cookie"]["config"]["multiSelectFilterItems"] = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getCookiesMultiSelectFilterItems();

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookie', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookie.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookie');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookieservice', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookieservice.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookieservice');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookiecartegories', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookiecartegories.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookiecartegories');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookiefrontend', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookiefrontend.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookiefrontend');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_externalscripts', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_externalscripts.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_externalscripts');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_variables', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_variables.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_variables');

})();