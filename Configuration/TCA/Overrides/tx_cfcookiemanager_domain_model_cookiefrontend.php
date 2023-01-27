<?php

//die("PK");

$buttonSelect = [
    ['Accept All', "accept_all"],
    ['Preferences', "settings"]
];

$palettes = [
    'hardFactsPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'name, identifier,enabled',
    ],
    'modalConsetnPallet' => [
        'label' => 'Consent Settings',
        'showitem' => 'title_consent_modal, --linebreak--, description_consent_modal, --linebreak--, primary_btn_text_consent_modal,primary_btn_role_consent_modal,--linebreak--,secondary_btn_text_consent_modal,secondary_btn_role_consent_modal, --linebreak--,  layout_consent_modal,transition_consent_modal, position_consent_modal ',
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
        ["bottom center" ,"bottom center","content-beside-text-img-above-right"],
        ["bottom right" ,"bottom right"],
        ["bottom left" ,"bottom left"],
        ["middle center" ,"middle center"],
        ["middle right" ,"middle right"],
        ["middle center" ,"middle center"],
        ["top center" ,"top center"],
        ["top right" ,"top right"],
        ["top center" ,"top center"],
    ],
    /* TODO MAKE Icons like imageorient in Text & media element
    'fieldWizard' => [
        'selectIcons' => [
            'disabled' => false,
        ],
    ]
    */
];

/*
 *

           'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.0',
                        0,
                        'content-beside-text-img-above-center',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.1',
                        1,
                        'content-beside-text-img-above-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.2',
                        2,
                        'content-beside-text-img-above-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.3',
                        8,
                        'content-beside-text-img-below-center',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.4',
                        9,
                        'content-beside-text-img-below-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.5',
                        10,
                        'content-beside-text-img-below-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.6',
                        17,
                        'content-inside-text-img-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.7',
                        18,
                        'content-inside-text-img-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.9',
                        25,
                        'content-beside-text-img-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.10',
                        26,
                        'content-beside-text-img-left',
                    ],
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
 */

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["transition_consent_modal"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["slide" ,"slide"],
    ]
];



$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookiefrontend"]["columns"]["layout_settings"]["config"] = [
    'type' => 'select',
    'renderType' => 'selectSingle',
    "items" => [
        ["box" ,"box"],
        ["bar", "bar"]
    ]
];
// right,left (available only if bar layout selected)
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


