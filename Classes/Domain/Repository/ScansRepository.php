<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for Scan records.
 *
 * Handles data access for scan records. Business logic for
 * initiating scans has been moved to ScanService.
 */
class ScansRepository extends Repository
{
    public function initializeObject(): void
    {
        $querySettings = GeneralUtility::makeInstance(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Get the most recent scan record.
     *
     * @return object|null The latest scan or null if none exist
     */
    public function getLatest(): ?object
    {
        $query = $this->createQuery();
        $query->setOrderings(['uid' => QueryInterface::ORDER_DESCENDING]);
        $query->setLimit(1);

        $result = $query->execute();
        return $result[0] ?? null;
    }

    /**
     * Get the site base URL for a storage page.
     *
     * @param int $storage The storage page UID
     * @return string The site base URL or empty string
     */
    public function getTarget(int $storage): string
    {
        $sites = GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();

        foreach ($sites as $rootsite) {
            if ($rootsite->getRootPageId() === $storage) {
                return $rootsite->getBase()->__toString();
            }
        }

        return '';
    }

    /**
     * Fetch scan records for storage and language with enriched data.
     *
     * @param array $storage Storage page UIDs
     * @param int|false $language Language ID or false to ignore language
     * @return array Enriched scan records
     */
    public function getScansForStorageAndLanguage(array $storage, int|false $language): array
    {
        $query = $this->createQuery();
        $querySettings = $query->getQuerySettings();

        $this->configureLanguage($querySettings, $language);
        $querySettings->setStoragePageIds($storage);

        $query->setOrderings(['crdate' => QueryInterface::ORDER_DESCENDING]);
        $scans = $query->execute();

        return $this->enrichScanRecords($scans);
    }

    /**
     * Find a scan record by its identifier.
     *
     * @param string $identifier The scan identifier
     * @return object|null The scan record or null
     */
    public function findByIdentCf(string $identifier): ?object
    {
        return $this->findOneByProperty('identifier', $identifier);
    }

    /**
     * Find a single record by a property value.
     *
     * @param string $propertyName The property name
     * @param mixed $propertyValue The property value
     * @return object|null The record or null
     */
    public function findOneByProperty(string $propertyName, mixed $propertyValue): ?object
    {
        $query = $this->createQuery();
        $query->matching($query->equals($propertyName, $propertyValue));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }

    /**
     * Configure language settings for query.
     *
     * @param object $querySettings The query settings
     * @param int|false $language The language ID or false
     */
    private function configureLanguage(object $querySettings, int|false $language): void
    {
        if ($language === false) {
            $querySettings->setRespectSysLanguage(false);
            return;
        }

        $languageAspect = new LanguageAspect(
            $language,
            $language,
            LanguageAspect::OVERLAYS_ON
        );
        $querySettings->setLanguageAspect($languageAspect);
    }

    /**
     * Enrich scan records with computed properties.
     * Calculates service counts by type and compliance status from the new scan format.
     *
     * @param iterable $scans The scan records
     * @return array Enriched scan data
     */
    private function enrichScanRecords(iterable $scans): array
    {
        $preparedScans = [];

        foreach ($scans as $scan) {
            $services = json_decode($scan->getProvider() ?? '', true) ?: [];

            $importableCount = 0;
            $configuredCount = 0;
            $unknownCount = 0;
            $isCompliant = true;

            foreach ($services as $service) {
                // Calculate compliance from services
                if (!($service['isCompliant'] ?? true)) {
                    $isCompliant = false;
                }

                $source = $service['source'] ?? '';
                $isUnknown = $service['isUnknown'] ?? false;
                $missingInConfig = $service['missingInConfig'] ?? false;

                // Count services by type
                if ($source === 'unknown' || $isUnknown) {
                    $unknownCount++;
                } elseif ($source === 'user_config') {
                    $configuredCount++;
                } elseif ($source === 'database' && $missingInConfig) {
                    $importableCount++;
                }
            }

            $scan->setFoundProvider($importableCount + $configuredCount);
            $scan->setUnknownProvider($unknownCount > 0 ? (string)$unknownCount : '');

            $properties = $scan->_getProperties();
            $properties['importableCount'] = $importableCount;
            $properties['configuredCount'] = $configuredCount;
            $properties['unknownCount'] = $unknownCount;
            $properties['isCompliant'] = $isCompliant;
            $properties['totalServices'] = count($services);

            $preparedScans[] = $properties;
        }

        return $preparedScans;
    }
}
