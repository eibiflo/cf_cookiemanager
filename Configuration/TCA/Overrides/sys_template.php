<?php
defined('TYPO3') || die();

//@Deprecated Registering static template, use SiteSets in Typo3 13.
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('cf_cookiemanager', 'Configuration/TypoScript', 'Coding Freaks Cookie Manager');

/*
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
 *
call_user_func(function () {
    $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
    if ($versionInformation->getMajorVersion() < 13) {
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('cf_cookiemanager', 'Configuration/TypoScript', 'Coding Freaks Cookie Manager');
    }
});
*/
