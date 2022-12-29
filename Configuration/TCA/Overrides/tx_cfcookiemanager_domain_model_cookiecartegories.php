<?php

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiecartegories"]["columns"]["cookie_services"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectMultipleSideBySide',
    'foreign_table' => 'tx_cfcookiemanager_domain_model_cookieservice',
    'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookieservice.category_suggestion = ###REC_FIELD_identifier###  ',
    'MM' => 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm',
    'size' => 10,
    'autoSizeMax' => 30,
    'maxitems' => 9999,
    'multiple' => 0,
    /*
    'multiSelectFilterItems' => [
        ['', ''],
        ['foo', 'foo'],
        ['bar', 'bar'],
    ],
    */
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
