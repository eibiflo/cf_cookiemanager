<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for CookieFrontend records.
 *
 * This repository provides pure data access methods for frontend configuration records.
 * Business logic has been extracted to dedicated services:
 * - ConsentConfigurationService: Language and consent modal configuration
 * - IframeManagerService: IframeManager JavaScript configuration
 * - ExternalScriptService: External script registration
 * - TrackingService: Consent tracking functionality
 * - ConfigurationBuilderService: Overall configuration orchestration
 *
 * @see \CodingFreaks\CfCookiemanager\Service\Frontend\ConsentConfigurationService
 * @see \CodingFreaks\CfCookiemanager\Service\Frontend\IframeManagerService
 * @see \CodingFreaks\CfCookiemanager\Service\Frontend\ExternalScriptService
 * @see \CodingFreaks\CfCookiemanager\Service\Frontend\TrackingService
 * @see \CodingFreaks\CfCookiemanager\Service\Config\ConfigurationBuilderService
 *
 * @extends Repository<\CodingFreaks\CfCookiemanager\Domain\Model\CookieFrontend>
 */
class CookieFrontendRepository extends Repository
{
    /**
     * Get frontend records by sys_language_uid and storage page IDs.
     *
     * Returns the first matching frontend record for the specified language,
     * ordered by creation date (oldest first).
     *
     * @param int $langUid The sys_language_uid to filter records
     * @param array $storage Array of storage page IDs
     * @return array Array containing the matching CookieFrontend model or empty array
     */
    public function getFrontendBySysLanguage(int $langUid = 0, array $storage = [1]): array
    {
        $query = $this->createQuery();

        $languageAspect = new LanguageAspect(
            $langUid,
            $langUid,
            LanguageAspect::OVERLAYS_ON
        );

        $query->getQuerySettings()
            ->setLanguageAspect($languageAspect)
            ->setStoragePageIds($storage);

        $query->setOrderings([
            'crdate' => QueryInterface::ORDER_ASCENDING,
        ])->setLimit(1);

        $result = $query->execute();
        $frontends = [];

        foreach ($result as $frontend) {
            $frontends[] = $frontend;
        }

        return $frontends;
    }

    /**
     * Get all frontend records from the specified storage page IDs.
     *
     * Returns all frontend records across all languages for the given storage pages.
     *
     * @param array $storage Array of storage page IDs
     * @return QueryResultInterface The query result containing all matching records
     */
    public function getAllFrontendsFromStorage(array $storage = [1]): QueryResultInterface
    {
        $query = $this->createQuery();

        $query->getQuerySettings()
            ->setStoragePageIds($storage)
            ->setRespectSysLanguage(false);

        return $query->execute();
    }
}
