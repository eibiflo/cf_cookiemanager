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
    ]
];