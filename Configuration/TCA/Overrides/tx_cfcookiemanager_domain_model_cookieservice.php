<?php

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["cookie"]["config"] = [
    'type' => 'select',
    'renderType' => 'CfSelectMultipleSideBySide',
    'foreign_table' => 'tx_cfcookiemanager_domain_model_cookie',
    'foreign_table_where' => 'tx_cfcookiemanager_domain_model_cookie.sys_language_uid = 0 AND tx_cfcookiemanager_domain_model_cookie.pid=###CURRENT_PID### AND tx_cfcookiemanager_domain_model_cookie.hidden = 0', //Selection only for default language, overlay is fetched by Repository
    'MM' => 'tx_cfcookiemanager_cookieservice_cookie_mm',
    'size' => 10,
    'autoSizeMax' => 30,
    'maxitems' => 9999,
    'behaviour' => [
        'allowLanguageSynchronization' => true
    ],
    'multiple' => 0,
    'itemsProcFunc' => CodingFreaks\CfCookiemanager\Utility\HelperUtility::class . '->itemsProcFuncCookies',
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

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["opt_in_code"]["config"] = [
    'type' => 'text',
    'renderType' => 't3editor',
    'format' => 'javascript',
    'cols' => 40,
    'rows' => 15,
    'default' => ''
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["opt_out_code"]["config"] = [
    'type' => 'text',
    'renderType' => 't3editor',
    'format' => 'javascript',
    'cols' => 40,
    'rows' => 15,
    'default' => ''
];

$GLOBALS["TCA"]["tx_cfcookiemanager_domain_model_cookieservice"]["columns"]["fallback_code"]["config"] = [
    'type' => 'text',
    'renderType' => 't3editor',
    'format' => 'javascript',
    'cols' => 40,
    'rows' => 15,
    'default' => ''
];

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
