<?php
defined('TYPO3') || die();

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["identifier"]["config"] = [
    'type' => 'input',
    'size' => 30,
    'eval' => 'trim',
    'default' => '',
    "readOnly" => false, //Disabled because v12 issue missing data-formengine-input-name for autoselect Detection
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["cookie_services"]["config"] = [
    'type' => 'select',
    'renderType' => 'CfSelectMultipleSideBySide',
    'foreign_table' => 'tx_cfcookiemanager_domain_model_cookieservice',
    'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookieservice.sys_language_uid = ###REC_FIELD_sys_language_uid### AND tx_cfcookiemanager_domain_model_cookieservice.pid=###CURRENT_PID###',
    'MM' => 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm',
    'size' => 10,
    'autoSizeMax' => 30,
    'maxitems' => 9999,
    'behaviour' => [
        'allowLanguageSynchronization' => true
    ],
    'multiple' => 0,
    'itemsProcFunc' => CodingFreaks\CfCookiemanager\Utility\HelperUtility::class . '->itemsProcFunc',
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
