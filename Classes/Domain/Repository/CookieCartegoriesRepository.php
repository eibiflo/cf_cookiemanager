<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap;
use TYPO3\CMS\Core\SingletonInterface;



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

    public function initializeObject()
    {
        // Einstellungen laden
        // $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
        // Einstellungen bearbeiten
        //$querySettings->setRespectSysLanguage(TRUE);
        //$querySettings->setStoragePageIds(array(1));
        //$querySettings->setLanguageOverlayMode(TRUE);
        //$querySettings->setRespectStoragePage(FALSE);
        // Einstellungen als Default setzen
        //$this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Returns all Categories from CodingFreaks CookieManager
     *
     * @param array $allCategorys
     * @throws \UnexpectedValueException
     * @return array
     */
    public function getAllCategories($storage)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(false)->setStoragePageIds($storage);
        $cookieCartegories = $query->execute();
        $allCategorys = [];
        foreach ($cookieCartegories as $category) {
            $allCategorys[] = $category;
        }
        return $allCategorys;
    }

    /**
     * @param $identifier
     */
    public function getCategoryByIdentifier($identifier,$langUid = 0,$storage=[1])
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid)->setStoragePageIds($storage);
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        /*
                if($langUid !== 0){
                    $queryParser = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser::class);
                    echo $queryParser->convertQueryToDoctrineQueryBuilder($query)->getSQL();
                }
        */

        return $query->execute();
    }
    public function getAllCategoriesFromAPI($lang)
    {
        $json = file_get_contents("https://cookieapi.coding-freaks.com/api/categories/".$lang);
        $services = json_decode($json, true);
        return $services;
    }

    /**
     * @param int $uid UID des Datensatzes in der Standardsprache
     * @param int $languageUid Sprache (sys_language_uid)
     * @return object
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





    public function insertFromAPI($lang)
    {
        foreach ($lang as $lang_config){
            if(empty($lang_config)){
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {
                $categories = $this->getAllCategoriesFromAPI($lang["language"]["twoLetterIsoCode"]);
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

                    $categoryDB = $this->getCategoryByIdentifier($category["identifier"],0,[$lang["rootSite"]]);
                    if (count($categoryDB) == 0) {
                        $this->add($categoryModel);
                        $this->persistenceManager->persistAll();
                    }

                    if($lang["language"]["languageId"] != 0){
                        $categoryDB = $this->getCategoryByIdentifier($category["identifier"],0,[$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getCategoryByIdentifier($category["identifier"],$lang["language"]["languageId"],[$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookiecartegories');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookiecartegories')->values([
                                'pid' => $lang["rootSite"],
                                'sys_language_uid' => $lang["language"]["languageId"],
                                'l10n_parent' => (int)$categoryDB[0]->getUid(),
                                'title' =>$categoryModel->getTitle(),
                                'identifier' =>$categoryModel->getIdentifier(),
                                'description' =>$categoryModel->getDescription(),
                                'is_required' =>$categoryModel->getIsRequired(),
                            ])
                                ->execute();
                        }
                    }

                }

            }

        }
    }
}
