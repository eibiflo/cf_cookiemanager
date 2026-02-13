<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022
 */

/**
 * The repository for CookieCartegories
 *
 * @extends \TYPO3\CMS\Extbase\Persistence\Repository<\CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories>
 */
class CookieCartegoriesRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * @var array<non-empty-string, 'ASC'|'DESC'>
     */
    protected $defaultOrderings = ['sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING];

    /**
     * Retrieve all categories based on the specified storage and language UID.
     *
     *
     * @param int[] $storage An array of storage page IDs where the categories can be found.
     * @param int $langUid The language UID (optional). If provided, categories will be retrieved in the specified language.
     *                          If set to false (default), categories will be retrieved in the default language.
     * @return \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories[] An array containing all categories fetched from the database.
     */
    public function getAllCategories($storage, $langUid = 0)
    {
        $query = $this->createQuery();

        if ($langUid !== false) {
            $languageAspect = new LanguageAspect((int)$langUid, (int)$langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
            $query->getQuerySettings()->setLanguageAspect($languageAspect);
            $query->getQuerySettings()->setStoragePageIds($storage);
        }

        $query->getQuerySettings()->setIgnoreEnableFields(false)->setStoragePageIds($storage);
        $cookieCartegories = $query->execute();
        $allCategorys = [];
        foreach ($cookieCartegories as $category) {
            $allCategorys[] = $category;
        }
        return $allCategorys;
    }

    /**
     * Retrieve a category by its identifier.
     *
     * This function queries the database to find a category with the given identifier. The category must match the
     * provided language UID and be associated with one of the specified storage page IDs. The result is limited to one
     * category, and it will be ordered by creation date in ascending order.
     *
     * @param string $identifier The identifier of the category to retrieve.
     * @param int $langUid The language UID (optional). The language in which the category should be retrieved. Default is 0.
     * @param int[] $storage An array of storage page IDs (optional). The storage pages where the category can be found. Default is [1].
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null The result of the query as a QueryResultInterface object or null if no category is found.
     */
    public function getCategoryByIdentifier($identifier, $langUid = 0, $storage = [1])
    {
        $query = $this->createQuery();
        $languageAspect = new LanguageAspect((int)$langUid, (int)$langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
        $query->getQuerySettings()->setLanguageAspect($languageAspect);
        $query->getQuerySettings()->setStoragePageIds($storage);
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    /**
     *
     * This function deletes the relationship between a service and a category in the database.
     *
     * @param int|string $category The UID or identifier of the category from which the service will be removed.
     * @param mixed $service The service object or UID whose relationship will be deleted from the category.
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function removeServiceFromCategory($category, $service)
    {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_cookiecartegories_cookieservice_mm');
        $queryBuilder
            ->delete('tx_cfcookiemanager_cookiecartegories_cookieservice_mm')
            ->where(
                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($service->getUid(), \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->executeStatement();
    }
}
