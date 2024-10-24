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

$palettes = [
    'hardFactsPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'name,enabled, --linebreak--, impress_text, impress_link , --linebreak--, data_policy_text, data_policy_link, --linebreak--, identifier',
    ],
    'modalConsetnPallet' => [
        'label' => 'Consent Settings',
        'showitem' => 'title_consent_modal, --linebreak--, description_consent_modal, revision_text, --linebreak--, primary_btn_text_consent_modal,primary_btn_role_consent_modal,--linebreak--,secondary_btn_text_consent_modal,secondary_btn_role_consent_modal, --linebreak--, tertiary_btn_text_consent_modal,tertiary_btn_role_consent_modal, --linebreak--,  layout_consent_modal,--linebreak--,transition_consent_modal,  --linebreak--, position_consent_modal ',
    ],
    'modalSettingsPallet' => [
        'label' => 'Consent Settings',
        'showitem' => 'title_settings, save_btn_settings, --linebreak--,accept_all_btn_settings, reject_all_btn_settings, --linebreak--, close_btn_settings, col1_header_settings, --linebreak--, col2_header_settings, col3_header_settings,  --linebreak--, blocks_title, --linebreak--, blocks_description, --linebreak--, position_settings, layout_settings, transition_settings ',
    ],
    'modalCustomizePallet' => [
        'label' => 'Consent Settings',
        'showitem' => 'custombutton,in_line_execution, --linebreak--, custom_button_html',
    ],
];

if (!empty($GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookiefrontend']["palettes"])) {
    $GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookiefrontend']["palettes"] = array_replace_recursive($GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookiefrontend']["palettes"], $palettes);
} else {
    $GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookiefrontend']["palettes"] = $palettes;
}


$standardpallets = " --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime";
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["types"]["1"]["showitem"] = '
    --div--;Consent Modal,--palette--;;modalConsetnPallet,
    --div--;Settings Modal ,--palette--;;modalSettingsPallet,
    --div--;Customize ,--palette--;;modalCustomizePallet,
    --div--;Global Settings, --palette--;;hardFactsPallet,
    
    ' . $standardpallets;


$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["primary_btn_role_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => $buttonSelect
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["secondary_btn_role_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => $buttonSelect
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["tertiary_btn_role_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => $buttonSelect
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_consent_modal"]["onChange"] = 'reload';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'box',
            'value' => 'box',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_box.svg'
        ],
        [
            'label' => 'cloud',
            'value' => 'cloud',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_cloud.svg'
        ],
        [
            'label' => 'bar',
            'value' => 'bar',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/layout/modal_bar.svg'
        ],
    ],
    'fieldWizard' => [
        'selectIcons' => [
            'disabled' => false,
        ],
    ]
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_consent_modal"]["displayCond"] = 'FIELD:layout_consent_modal:!=:bar';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'top left',
            'value' => 'top left',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_oben.svg'
        ],
        [
            'label' => 'top center',
            'value' => 'middle center',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_mitte_oben.svg'
        ],
        [
            'label' => 'top right',
            'value' => 'top right',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_oben.svg'
        ],
        [
            'label' => 'middle left',
            'value' => 'middle left',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_mitte.svg'
        ],
        [
            'label' => 'middle center',
            'value' => 'middle center',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_mitte_mitte.svg'
        ],
        [
            'label' => 'middle right',
            'value' => 'middle right',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_mitte.svg'
        ],
        [
            'label' => 'bottom left',
            'value' => 'bottom left',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_links_unten.svg'
        ],
        [
            'label' => 'bottom center',
            'value' => 'bottom center',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_unten_mitte.svg'
        ],
        [
            'label' => 'bottom right',
            'value' => 'bottom right',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/consent/modal_rechts_unten.svg'
        ],
    ],
    'fieldWizard' => [
        'selectIcons' => [
            'disabled' => false,
        ],
    ]

];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["transition_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'slide',
            'value' => 'slide',
        ],
    ]
];


$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_settings"]["onChange"] = 'reload';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'box',
            'value' => 'box',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_mitte.svg'
        ],
        [
            'label' => 'bar',
            'value' => 'bar',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_links.svg'
        ],
    ],
    'fieldWizard' => [
        'selectIcons' => [
            'disabled' => false,
        ],
    ]
];

// right,left (available only if bar layout selected)
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_settings"]["displayCond"] = 'FIELD:layout_settings:=:bar';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'left',
            'value' => 'left',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_links.svg'
        ],
        [
            'label' => 'right',
            'value' => 'right',
            'icon' => 'EXT:cf_cookiemanager/Resources/Public/Icons/backend/position/settings/settings_rechts.svg'
        ],
    ],
    'fieldWizard' => [
        'selectIcons' => [
            'disabled' => false,
        ],
    ]
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["transition_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        [
            'label' => 'slide',
            'value' => 'slide',
        ],
    ]
];


