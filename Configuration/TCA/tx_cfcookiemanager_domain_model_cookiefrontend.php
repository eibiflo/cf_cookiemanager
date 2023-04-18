<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
        'searchFields' => 'identifier,name,title_consent_modal,description_consent_modal,primary_btn_text_consent_modal,primary_btn_role_consent_modal,secondary_btn_text_consent_modal,secondary_btn_role_consent_modal,tertiary_btn_text_consent_modal,tertiary_btn_role_consent_modal,title_settings,save_btn_settings,accept_all_btn_settings,reject_all_btn_settings,close_btn_settings,col1_header_settings,col2_header_settings,col3_header_settings,blocks_title,blocks_description,custom_button_html,layout_consent_modal,layout_settings,position_consent_modal,position_settings,transition_consent_modal,transition_settings',
        'iconfile' => 'EXT:cf_cookiemanager/Resources/Public/Icons/tx_cfcookiemanager_domain_model_cookiefrontend.gif'
    ],
    'types' => [
        '1' => ['showitem' => 'identifier, name, enabled, title_consent_modal, description_consent_modal, primary_btn_text_consent_modal, primary_btn_role_consent_modal, secondary_btn_text_consent_modal, secondary_btn_role_consent_modal,tertiary_btn_text_consent_modal,tertiary_btn_role_consent_modal, title_settings, save_btn_settings, accept_all_btn_settings, reject_all_btn_settings, close_btn_settings, col1_header_settings, col2_header_settings, col3_header_settings, blocks_title, blocks_description, custombutton, custom_button_html, in_line_execution, layout_consent_modal, layout_settings, position_consent_modal, position_settings, transition_consent_modal, transition_settings, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
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
                'foreign_table' => 'tx_cfcookiemanager_domain_model_cookiefrontend',
                'foreign_table_where' => 'AND {#tx_cfcookiemanager_domain_model_cookiefrontend}.{#pid}=###CURRENT_PID### AND {#tx_cfcookiemanager_domain_model_cookiefrontend}.{#sys_language_uid} IN (-1,0)',
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

        'identifier' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.identifier',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'enabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.enabled',
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
        'title_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'description_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.description_consent_modal',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
            
        ],
        'revision_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.revision_text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],

        ],
        'primary_btn_text_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.primary_btn_text_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'primary_btn_role_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.primary_btn_role_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'secondary_btn_text_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.secondary_btn_text_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'secondary_btn_role_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.secondary_btn_role_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'tertiary_btn_text_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tertiary_btn_text_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'tertiary_btn_role_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tertiary_btn_role_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'title_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'save_btn_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.save_btn_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'accept_all_btn_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.accept_all_btn_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'reject_all_btn_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.reject_all_btn_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'close_btn_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.close_btn_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'col1_header_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col1_header_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'col2_header_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col2_header_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'col3_header_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col3_header_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'blocks_title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.blocks_title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'blocks_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.blocks_description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'default',
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
            ],
            
        ],
        'custombutton' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.custombutton',
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
        'custom_button_html' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.custom_button_html',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'in_line_execution' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.in_line_execution',
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
        'layout_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'layout_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'position_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'position_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'transition_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_consent_modal',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'transition_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_settings',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
    
    ],
];
