<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
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
 */
class CookieCartegoriesRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieServiceRepository = null;

    /**
     * @var array
     */
    protected $defaultOrderings = ['sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING];

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    /**
     * Retrieve all categories based on the specified storage and language UID.
     *
     *
     * @param array[] $storage An array of storage page IDs where the categories can be found.
     * @param int|bool $langUid The language UID (optional). If provided, categories will be retrieved in the specified language.
     *                          If set to false (default), categories will be retrieved in the default language.
     * @return \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories[] An array containing all categories fetched from the database.
     */
    public function getAllCategories($storage, $langUid = false)
    {
        $query = $this->createQuery();

        if ($langUid !== false) {
            $query->getQuerySettings()->setLanguageUid(intval($langUid));
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
     * @param array[] $storage An array of storage page IDs (optional). The storage pages where the category can be found. Default is [1].
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null The result of the query as a QueryResultInterface object or null if no category is found.
     */
    public function getCategoryByIdentifier($identifier, $langUid = 0, $storage = [1])
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid)->setStoragePageIds($storage);
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    /**
     * Retrieve all categories from the API based on the specified language.
     *
     * This function fetches category data from an external API endpoint based on the provided language code.
     * The API endpoint is obtained from the cf_cookiemanager extension's configuration.
     *
     * @param string $lang The language code (e.g., 'en', 'de') for which categories will be fetched from the API.
     * @return array An array representing the categories retrieved from the API. The array contains associative arrays
     *               with category information. If the API endpoint is not defined or there is an error during the API
     *               request, an empty array will be returned.
     */
    public function getAllCategoriesFromAPI($lang)
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        if (!empty($extensionConfiguration["endPoint"])) {
            $json = file_get_contents($extensionConfiguration["endPoint"] . "categories/" . $lang);
            $services = json_decode($json, true);
            return $services;
        }
        return [];
    }

    /**
     * Find a translated record by its UID and language UID.
     *
     * The function searches for the translation of the record specified by the given UID and the language specified
     * by the language UID.
     *
     * @param int $uid The UID of the record for which the translation will be searched.
     * @param int $languageUid The language UID of the translation to be found.
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface|null The result of the query as a QueryResultInterface object or null if no translation is found.
     */
    public function findTranslationByUid($uid, $languageUid)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $query->matching(
            $query->logicalAnd(
                $query->equals('l10n_parent', $uid),
                $query->equals('sys_language_uid', $languageUid)
            )
        );
        return $query->setLimit(1)->execute()->getFirst();
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
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();

        $queryBuilder = $con->createQueryBuilder();
        $queryBuilder->delete('tx_cfcookiemanager_cookiecartegories_cookieservice_mm')
            ->andWhere('uid_foreign = :service')
            ->setParameter('service', $service->getUid())
            ->executeQuery();
    }

    /**
     * Insert categories from the API into the database for specified languages.
     *
     * This function fetches category data from an external API for each language specified in the $lang array.
     * It inserts the retrieved categories into the database as new records if they do not already exist.
     * If the categories already exist, the function checks if translations exist for the category in the specified
     * language and inserts translations if necessary.
     *
     * @param array $lang An array containing configurations for different languages.
     */
    public function insertFromAPI($lang)
    {
        foreach ($lang as $lang_config) {
            if (empty($lang_config)) {
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {
                $categories = $this->getAllCategoriesFromAPI($lang["langCode"]);
                //TODO Error handling
                foreach ($categories as $category) {
                    $categoryModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories();
                    $categoryModel->setPid($lang["rootSite"]);
                    $categoryModel->setTitle($category["title"]);
                    $categoryModel->setIdentifier($category["identifier"]);
                    $categoryModel->setDescription($category["description"] ?? "");
                    if (!empty($category["is_required"])) {
                        $categoryModel->setIsRequired((int)$category["is_required"]);
                    }

                    $categoryDB = $this->getCategoryByIdentifier($category["identifier"], 0, [$lang["rootSite"]]);
                    if (count($categoryDB) == 0) {
                        $this->add($categoryModel);
                        $this->persistenceManager->persistAll();
                    }

                    if ($lang["language"]["languageId"] != 0) {
                        $categoryDB = $this->getCategoryByIdentifier($category["identifier"], 0, [$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getCategoryByIdentifier($category["identifier"], $lang["language"]["languageId"], [$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookiecartegories');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookiecartegories')->values([
                                'pid' => $lang["rootSite"],
                                'sys_language_uid' => $lang["language"]["languageId"],
                                'l10n_parent' => (int)$categoryDB[0]->getUid(),
                                'title' => $categoryModel->getTitle(),
                                'identifier' => $categoryModel->getIdentifier(),
                                'description' => $categoryModel->getDescription(),
                                'is_required' => $categoryModel->getIsRequired(),
                            ])
                                ->executeQuery();
                        }
                    }

                }

            }

        }
    }
}
