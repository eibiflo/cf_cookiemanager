<?php


//\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\CodingFreaks\CfCookiemanager\Utility\HelperUtility::class);

//$itemGroups = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getCookieServicesFilteritemGroups();

//\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($itemGroups);


$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["cookie_services"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectMultipleSideBySide',
    'foreign_table' => 'tx_cfcookiemanager_domain_model_cookieservice',
    'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookieservice.category_suggestion = ###REC_FIELD_identifier###  AND tx_cfcookiemanager_domain_model_cookieservice.sys_language_uid = ###REC_FIELD_sys_language_uid### ',
    //'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookieservice.sys_language_uid = ###REC_FIELD_sys_language_uid### ',
    'MM' => 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm',
    'size' => 10,
    'autoSizeMax' => 30,
    'maxitems' => 9999,
    'multiple' => 0,
    //'itemsProcFunc' => 'CodingFreaks\CfCookiemanager\Utility\HelperUtility->sortServices',
    //'itemsProcConfig' => [
    //    'table' => 'tx_cfcookiemanager_domain_model_cookiecartegories'
    //],
    //'multiSelectFilterItems' => $multiSelectFilterItems,
    //'itemGroups' => $itemGroups,

    'fieldControl' => [
        'editPopup' => [
            'disabled' => false,
        ],
        'addRecord' => [
            'disabled' => false,
        ],
        'listModule' => [
            'disabled' => true,
        ],
    ],
];
