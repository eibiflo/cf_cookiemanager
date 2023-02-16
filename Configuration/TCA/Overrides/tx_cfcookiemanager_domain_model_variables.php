<?php

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_variables"]["columns"]['identifier'] = [
    'exclude' => true,
    'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_variables.identifier',
    "description"=> "if your Variable is not shown, Save the Record and refresh the form.",
    "config" => [
        'type' => 'select',
        'renderType' => 'selectSingle',
        "items" => [
            [" " ," "],
        ],
        'itemsProcFunc' => 'CodingFreaks\CfCookiemanager\Utility\HelperUtility->getVariablesFromItem',
    ]
];