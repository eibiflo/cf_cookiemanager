<?php

$buttonSelect = [
    [
        'label' => 'Accept All',
        'value' => 'accept_all',
    ],
    [
        'label' => 'Preferences',
        'value' => 'settings',
    ],
    [
        'label' => 'Reject all',
        'value' => 'accept_necessary',
    ],
    [
        'label' => 'Hide Button',
        'value' => 'display_none',
    ],
];

return [
    'ctrl' => [
        'title' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend',
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
        'iconfile' => 'EXT:cf_cookiemanager/Resources/Public/Icons/tx_cfcookiemanager_domain_model_cookiefrontend.svg'
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tab.consentModal, --palette--;;consentModal,
                --div--;LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tab.settingsModal, --palette--;;settingsModal,
                --div--;LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tab.customize, --palette--;;customize,
                --div--;LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tab.global, --palette--;;global,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime
            ',
        ],
    ],
    'palettes' => [
        'consentModal' => [
            'showitem' => 'title_consent_modal, --linebreak--, description_consent_modal, revision_text, --linebreak--, primary_btn_text_consent_modal, primary_btn_role_consent_modal, --linebreak--, secondary_btn_text_consent_modal, secondary_btn_role_consent_modal, --linebreak--, tertiary_btn_text_consent_modal, tertiary_btn_role_consent_modal, --linebreak--, layout_consent_modal, --linebreak--, transition_consent_modal, --linebreak--, position_consent_modal',
        ],
        'settingsModal' => [
            'showitem' => 'title_settings, save_btn_settings, --linebreak--, accept_all_btn_settings, reject_all_btn_settings, --linebreak--, close_btn_settings, col1_header_settings, --linebreak--, col2_header_settings, col3_header_settings, --linebreak--, blocks_title, --linebreak--, blocks_description, --linebreak--, position_settings, layout_settings, transition_settings',
        ],
        'customize' => [
            'showitem' => 'custombutton, in_line_execution, --linebreak--, custom_button_html',
        ],
        'global' => [
            'showitem' => 'name, enabled, --linebreak--, impress_text, impress_link, --linebreak--, data_policy_text, data_policy_link, --linebreak--, identifier, exclude_from_update',
        ],
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

        'identifier' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.identifier',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.identifier.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.name.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.enabled.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ]
                ],
                'default' => 0,
            ]
        ],
        'title_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_consent_modal.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.description_consent_modal.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.revision_text.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.primary_btn_text_consent_modal.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.primary_btn_role_consent_modal.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $buttonSelect,
            ],
        ],
        'secondary_btn_text_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.secondary_btn_text_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.secondary_btn_text_consent_modal.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.secondary_btn_role_consent_modal.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $buttonSelect,
            ],
        ],
        'tertiary_btn_text_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tertiary_btn_text_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tertiary_btn_text_consent_modal.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.tertiary_btn_role_consent_modal.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $buttonSelect,
            ],
        ],
        'title_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_settings',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.title_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.save_btn_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.accept_all_btn_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.reject_all_btn_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.close_btn_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col1_header_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col2_header_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.col3_header_settings.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.blocks_title.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.blocks_description.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.custombutton.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ]
                ],
                'default' => 0,
            ]
        ],
        'custom_button_html' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.custom_button_html',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.custom_button_html.description',
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
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.in_line_execution.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'value' => '',
                    ]
                ],
                'default' => 0,
            ]
        ],
        'layout_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_consent_modal.description',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'box',
                        'value' => 'box',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_box.svg',
                    ],
                    [
                        'label' => 'cloud',
                        'value' => 'cloud',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_cloud.svg',
                    ],
                    [
                        'label' => 'bar',
                        'value' => 'bar',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_bar.svg',
                    ],
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'position_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_consent_modal.description',
            'displayCond' => 'FIELD:layout_consent_modal:!=:bar',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'top left',
                        'value' => 'top left',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_oben.svg',
                    ],
                    [
                        'label' => 'top center',
                        'value' => 'middle center',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_mitte_oben.svg',
                    ],
                    [
                        'label' => 'top right',
                        'value' => 'top right',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_oben.svg',
                    ],
                    [
                        'label' => 'middle left',
                        'value' => 'middle left',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_mitte.svg',
                    ],
                    [
                        'label' => 'middle center',
                        'value' => 'middle center',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_mitte_mitte.svg',
                    ],
                    [
                        'label' => 'middle right',
                        'value' => 'middle right',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_mitte.svg',
                    ],
                    [
                        'label' => 'bottom left',
                        'value' => 'bottom left',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_unten.svg',
                    ],
                    [
                        'label' => 'bottom center',
                        'value' => 'bottom center',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_unten_mitte.svg',
                    ],
                    [
                        'label' => 'bottom right',
                        'value' => 'bottom right',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_unten.svg',
                    ],
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'transition_consent_modal' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_consent_modal',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_consent_modal.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'slide',
                        'value' => 'slide',
                    ],
                ],
            ],
        ],
        'layout_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_settings',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.layout_settings.description',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'box',
                        'value' => 'box',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_mitte.svg',
                    ],
                    [
                        'label' => 'bar',
                        'value' => 'bar',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_links.svg',
                    ],
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'position_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_settings',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.position_settings.description',
            'displayCond' => 'FIELD:layout_settings:=:bar',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'left',
                        'value' => 'left',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_links.svg',
                    ],
                    [
                        'label' => 'right',
                        'value' => 'right',
                        'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_rechts.svg',
                    ],
                ],
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'transition_settings' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_settings',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.transition_settings.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'slide',
                        'value' => 'slide',
                    ],
                ],
            ],
        ],
        'impress_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.impress_text',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.impress_text.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'impress_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.impress_link',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.impress_link.description',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url', 'record'],
            ]
        ],
        'data_policy_text' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.data_policy_text',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.data_policy_text.description',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
                'default' => ''
            ],
        ],
        'data_policy_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.data_policy_link',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.data_policy_link.description',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['page', 'url', 'record'],
            ]
        ],
        'exclude_from_update' => [
            'label' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookieservice.exclude_from_update',
            'description' => 'LLL:EXT:cf_cookiemanager/Resources/Private/Language/locallang_db.xlf:tx_cfcookiemanager_domain_model_cookiefrontend.exclude_from_update.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        'label' => '',
                        'labelChecked' => 'Can be Updated',
                        'labelUnchecked' => 'Ignored in Update interface',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
    ],
];
