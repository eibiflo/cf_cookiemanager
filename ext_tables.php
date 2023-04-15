<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function() {

    ExtensionManagementUtility::addModule(
        'web',
        'cookiesettings',
        '',
        '',
        [
            'routeTarget' => \CodingFreaks\CfCookiemanager\Controller\CookieSettingsBackendController::class . '::mainAction',
            'access' => 'user,group',
            'name' => 'web_cookiesettings',
            'iconIdentifier' => 'cf_cookiemanager-plugin-cookiefrontend',
            'labels' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_cookiesettings.xlf',
        ]
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookie', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookie.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookie');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookieservice', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookieservice.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookieservice');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookiecartegories', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookiecartegories.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookiecartegories');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_conntentoverride', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_conntentoverride.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_conntentoverride');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_cookiefrontend', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_cookiefrontend.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_cookiefrontend');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_externalscripts', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_externalscripts.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_externalscripts');

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_cfcookiemanager_domain_model_variables', 'EXT:cf_cookiemanager/Resources/Private/Language/locallang_csh_tx_cfcookiemanager_domain_model_variables.xlf');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_cfcookiemanager_domain_model_variables');

})();