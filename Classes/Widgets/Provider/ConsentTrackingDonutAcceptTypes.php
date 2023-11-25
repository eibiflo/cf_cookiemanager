<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace CodingFreaks\CfCookiemanager\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

class ConsentTrackingDonutAcceptTypes implements ChartDataProviderInterface
{
    public function __construct(private readonly LanguageServiceFactory $languageServiceFactory) {}

    public function getChartData(): array
    {
        $necessary = $this->getNumberOfRecords("necessary");
        $custom = $this->getNumberOfRecords("custom");
        $all = $this->getNumberOfRecords("all");

        return [
            'labels' => [
                "Strictly Necessary",
                "Custom selection",
                "All",
            ],
            'datasets' => [
                [
                    'backgroundColor' => [
                        '#263238',
                        '#546e7a',
                        '#78909c',
                    ],
                    'data' => [$necessary, $custom,$all],
                ],
            ],
        ];
    }

    protected function getNumberOfRecords(string $type): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_tracking');
        return (int)$queryBuilder
            ->count('uid')
            ->from('tx_cfcookiemanager_domain_model_tracking')
            ->where(
                $queryBuilder->expr()->eq(
                    'consent_type',
                    $queryBuilder->createNamedParameter($type, Connection::PARAM_STR)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }
}
