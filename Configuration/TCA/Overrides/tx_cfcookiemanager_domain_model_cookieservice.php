<?php
//TODO CfSelectMultipleSideBySide
//TODO Select Groups like in Categories with Filter
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["cookie"]["config"] = [
        'type' => 'select',
        'renderType' => 'selectMultipleSideBySide',
        'foreign_table' => 'tx_cfcookiemanager_domain_model_cookie',
        'MM' => 'tx_cfcookiemanager_cookieservice_cookie_mm',
        'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookie.service_identifier = ###REC_FIELD_identifier###  ',
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

];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["iframe_thumbnail_url"]["config"] = [
        'type' => 'text',
        'renderType' => 't3editor',
        'format' => 'javascript',
        'cols' => 40,
        'rows' => 15,
        'default' => ''
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["iframe_embed_url"]["config"] = [
        'type' => 'text',
        'renderType' => 't3editor',
        'format' => 'javascript',
        'cols' => 40,
        'rows' => 15,
        'default' => ''
];

//       '1' => ['showitem' => ' opt_in_code, opt_out_code, fallback_code , external_scripts, variable_priovider, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
//contentoverride todo remove
$palettes = [
    'hardFactsPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'name, identifier, provider, category_suggestion, dsgvo_link, --linebreak--, description, --linebreak--, cookie',
    ],
    'iframeManagerPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'iframe_embed_url,  --linebreak--, iframe_thumbnail_url,  --linebreak--, iframe_notice,  --linebreak--, iframe_load_btn,  --linebreak--,iframe_load_all_btn',
    ],
    'scriptPallet' => [
        'label' => 'hardFactsPallet Settings',
        'showitem' => 'opt_in_code, --linebreak--, opt_out_code, --linebreak--,fallback_code, --linebreak--,external_scripts, --linebreak--,variable_priovider',
    ],

];

if(!empty($GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookieservice']["palettes"])){
    $GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookieservice']["palettes"] = array_replace_recursive($GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookiefrontend']["palettes"], $palettes);
}else{
    $GLOBALS['TCA']['tx_cfcookiemanager_domain_model_cookieservice']["palettes"] = $palettes;
}
$standardPallets = "--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime";
$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["types"]["1"]["showitem"] =
    '--div--;Global Settings, --palette--;;hardFactsPallet,
    --div--;Iframe Manager,--palette--;;iframeManagerPallet ,
    --div--;Scripts ,--palette--;;scriptPallet ,
    
    '.$standardPallets;
