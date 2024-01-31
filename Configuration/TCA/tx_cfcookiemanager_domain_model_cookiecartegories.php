<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
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
        'searchFields' => 'title,description,identifier',
        'iconfile' => 'EXT:cf_cookiemanager/Resources/Public/Icons/tx_cfcookiemanager_domain_model_cookiecartegories.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'title, description, identifier, is_required, cookie_services, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
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
                    ['', 0],
                ],
                'foreign_table' => 'tx_cfcookiemanager_domain_model_cookiecartegories',
                'foreign_table_where' => 'AND {#tx_cfcookiemanager_domain_model_cookiecartegories}.{#pid}=###CURRENT_PID### AND {#tx_cfcookiemanager_domain_model_cookiecartegories}.{#sys_language_uid} IN (-1,0)',
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
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true
                    ]
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
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
                'type' => 'input',
                'renderType' => 'inputDateTime',
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

        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'identifier' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories.identifier',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'is_required' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories.is_required',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ]
                ],
                'default' => 0,
            ]
        ],
        'cookie_services' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiecartegories.cookie_services',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_cfcookiemanager_domain_model_cookieservice',
                'MM' => 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm',
                'size' => 10,
                'autoSizeMax' => 30,
                'maxitems' => 9999,
                'multiple' => 0,
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
            ],
            
        ],
    
    ],
];
