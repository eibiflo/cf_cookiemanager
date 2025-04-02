<?php

use CodingFreaks\CfCookiemanager\Controller\BackendAjax;

return [
    'cfcookiemanager_ajax_checkfordatabaseupdates' => [
        'path' => '/cf-cookiemanager/ajax/check-for-database-updates',
        'target' => BackendAjax\UpdateCheckController::class . '::checkForUpdatesAction',
    ],

    'cfcookiemanager_ajax_updatedataset' => [
        'path' => '/cf-cookiemanager/ajax/update-dataset',
        'target' => BackendAjax\UpdateCheckController::class . '::updateDatasetAction',
    ],

    'cfcookiemanager_ajax_insertdataset' => [
        'path' => '/cf-cookiemanager/ajax/insert-dataset',
        'target' => BackendAjax\UpdateCheckController::class . '::insertDatasetAction',
    ],

    'cfcookiemanager_ajax_installdatasets' => [
        'path' => '/cf-cookiemanager/ajax/install-datasets',
        'target' => BackendAjax\InstallController::class . '::installDatasetsAction',
    ],

    'cfcookiemanager_ajax_uploaddataset' => [
        'path' => '/cf-cookiemanager/ajax/upload-datasets',
        'target' => BackendAjax\InstallController::class . '::uploadDatasetAction',
    ],

    'cfcookiemanager_ajax_checkapidata' => [
        'path' => '/cf-cookiemanager/ajax/check-api-data',
        'target' => BackendAjax\InstallController::class . '::checkApiDataAction',
    ],

    'cfcookiemanager_ajax_clearthumbnailcache' => [
        'path' => '/cf-cookiemanager/ajax/clear-thumbnail-cache',
        'target' => BackendAjax\ThumbnailController::class . '::clearThumbnailCache',
    ],
];