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
    public function getAllCategories($request)
    {

        $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $cookieCartegories = $this->findAll();
        $allCategorys = [];
        foreach ($cookieCartegories as $category) {
            $uriParameters = [
                'edit' => ['tx_cfcookiemanager_domain_model_cookiecartegories' => [$category->getUid() => 'edit']],
                "returnUrl" => urldecode($request->getAttribute('normalizedParams')->getRequestUri()),
            ];
            $categoryTemp = [];
            $categoryTemp["linkEdit"] = $backendUriBuilder->buildUriFromRoute('record_edit', $uriParameters);

            #
            $categoryTemp["category"] = $category;
            $allCategorys[] = $categoryTemp;
        }
        return $allCategorys;
    }

    /**
     * @param $identifier
     */
    public function getCategoryByIdentifier($identifier,$langUid = 0)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid);
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
        $json = file_get_contents("http://cookieapi.coding-freaks.com/api/categories/".$lang);
        $services = json_decode($json, true);
        return $services;
    }

    /*
     *              $record = $this->findByUid($uid);
                    $translationRecord = $this->translationRepository->findByUid($translationUid);
                    $record->setLocalizedUid($translationRecord->getUid());
                    $this->myRepository->update($record);
                    $this->persistenceManager->persistAll();
     */

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
            $categories = $this->getAllCategoriesFromAPI($lang_config["iso-639-1"]);

            //TODO Error handling
            foreach ($categories as $category) {
                $categoryModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories();
                $categoryModel->setTitle($category["title"]);
                $categoryModel->setIdentifier($category["identifier"]);
                $categoryModel->setDescription($category["description"] ?? "");
                if (!empty($category["is_required"])) {
                    $categoryModel->setIsRequired((int)$category["is_required"]);
                }

                $categoryDB = $this->getCategoryByIdentifier($category["identifier"]);
                if (count($categoryDB) == 0) {
                    $this->add($categoryModel);
                    $this->persistenceManager->persistAll();
                }

                if($lang_config["languageId"] != 0){
                    $categoryDB = $this->getCategoryByIdentifier($category["identifier"],0); // $lang_config["languageId"]
                    $allreadyTranslated = $this->getCategoryByIdentifier($category["identifier"],$lang_config["languageId"]);
                    if (count($allreadyTranslated) == 0) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookiecartegories');
                        $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookiecartegories')->values([
                                'pid' =>1,
                                'sys_language_uid' => $lang_config["languageId"],
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
