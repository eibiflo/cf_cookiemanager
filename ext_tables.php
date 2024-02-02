<?php
defined('TYPO3') || die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

(static function() {

    $GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["cookie_services"]["config"]["multiSelectFilterItems"] = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getCookieServicesMultiSelectFilterItems();
    $GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["cookie"]["config"]["multiSelectFilterItems"] = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getCookiesMultiSelectFilterItems();

})();