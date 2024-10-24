<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'security' => [
           'ignorePageTypeRestriction' => true,
           'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => 'name,domain,secure,path,description,service_identifier',
        'iconfile' => 'EXT:cf_cookiemanager/Resources/Public/Icons/tx_cfcookiemanager_domain_model_cookie.svg'
    ],
    'types' => [
        '1' => ['showitem' => 'name, http_only, domain, secure, path, description, expiry, is_regex, service_identifier, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'value' => 0,
                    ],
                ],
                'foreign_table' => 'tx_cfcookiemanager_domain_model_cookie',
                'foreign_table_where' => 'AND {#tx_cfcookiemanager_domain_model_cookie}.{#pid}=###CURRENT_PID### AND {#tx_cfcookiemanager_domain_model_cookie}.{#sys_language_uid} IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],

        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'http_only' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.http_only',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'default' => 0
            ]
        ],
        'domain' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.domain',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'secure' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.secure',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'path' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.path',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'expiry' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.expiry',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'default' => 0
            ]
        ],
        'is_regex' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.is_regex',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [['label' => '', 'value' => '']],
                'default' => 0,
            ]
        ],
        'service_identifier' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookie.service_identifier',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
    
    ],
];
