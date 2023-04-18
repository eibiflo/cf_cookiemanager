<?php

//die("PK");

$buttonSelect = [
    ['Accept All', "accept_all"],
    ['Preferences', "settings"],
    ['Reject all', "accept_necessary"],
    ['Hide Button', "display_none"],
];

$palettes = [
    'hardFactsPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'name, identifier,enabled',
    ],
    'modalConsetnPallet' => [
        'label' => 'Consent Settings',
        'showitem' => 'title_consent_modal, --linebreak--, description_consent_modal, --linebreak--, primary_btn_text_consent_modal,primary_btn_role_consent_modal,--linebreak--,secondary_btn_text_consent_modal,secondary_btn_role_consent_modal, --linebreak--, tertiary_btn_text_consent_modal,tertiary_btn_role_consent_modal, --linebreak--,  layout_consent_modal,--linebreak--,transition_consent_modal,  --linebreak--, position_consent_modal ',
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

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["box" ,"box"],
        ["cloud", "cloud"],
        ["bar", "bar"]
    ]
];
//bottom,middle,top + left,right,center
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["bottom center" ,"bottom center","cf_cookiemanager-position-bottom-center"],
        ["middle center" ,"middle center","cf_cookiemanager-position-center-center"],
        ["top center" ,"top center","cf_cookiemanager-position-top-center"],

        ["middle left" ,"middle left","cf_cookiemanager-position-center-left"],
        ["middle right" ,"middle right","cf_cookiemanager-position-center-right"],

        ["top left" ,"top left","cf_cookiemanager-position-top-left"],
        ["top right" ,"top right","cf_cookiemanager-position-top-right"],

        ["bottom left" ,"bottom left","cf_cookiemanager-position-bottom-left"],
        ["bottom right" ,"bottom right","cf_cookiemanager-position-bottom-right"],
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
        ["slide" ,"slide"],
    ]
];


$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_settings"]["onChange"] = 'reload';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["box" ,"box"],
        ["bar", "bar"]
    ]
];

// right,left (available only if bar layout selected)
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_settings"]["displayCond"] = 'FIELD:layout_settings:=:bar';
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["position_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["right" ,"right"],
        ["left", "left"]
    ]
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["transition_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["slide" ,"slide"],
    ]
];


