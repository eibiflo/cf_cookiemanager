<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

/**
 * Provides chart data for sys log errors.
 */
class ConsentTrackingDataProvider implements ChartDataProviderInterface
{
    /**
     * Number of days to gather information for.
     *
     * @var int
     */
    protected $days = 7;

    /**
     * @var array
     */
    protected $labels = [];

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(int $days = 7)
    {
        $this->days = $days;
    }

    public function getChartData(): array
    {
        $this->calculateDataForLastDays();

        return [
            'labels' => $this->labels,
            'datasets' => [
                [
                    'label' => "Consent-Modal activities",
                    'backgroundColor' => WidgetApi::getDefaultChartColors()[4],
                    'border' => 0,
                    'data' => $this->data,
                ],
            ],
        ];
    }

    protected function getNumberOfErrorsInPeriod(int $start, int $end): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_tracking');
        return (int)$queryBuilder
            ->count('*')
            ->from('tx_cfcookiemanager_domain_model_tracking')
            ->where(
                $queryBuilder->expr()->gte(
                    'consent_date',
                    $queryBuilder->createNamedParameter($start, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->lte(
                    'consent_date',
                    $queryBuilder->createNamedParameter($end, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    protected function calculateDataForLastDays(): void
    {
        $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';

        for ($daysBefore = $this->days; $daysBefore >= 0; $daysBefore--) {
            $this->labels[] = date($format, (int)strtotime('-' . $daysBefore . ' day'));
            $startPeriod = (int)strtotime('-' . $daysBefore . ' day 0:00:00');
            $endPeriod =  (int)strtotime('-' . $daysBefore . ' day 23:59:59');

            $this->data[] = $this->getNumberOfErrorsInPeriod($startPeriod, $endPeriod);
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
