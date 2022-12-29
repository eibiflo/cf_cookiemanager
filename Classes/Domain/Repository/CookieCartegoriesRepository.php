<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

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
    public function initializeObject()
    {

        // Einstellungen laden
        $querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');

        // Einstellungen bearbeiten
        //$querySettings->setRespectSysLanguage(FALSE);
        //$querySettings->setStoragePageIds(array(1));
        $querySettings->setLanguageOverlayMode(FALSE);
        $querySettings->setRespectStoragePage(FALSE);

        // Einstellungen als Default setzen
        $this->setDefaultQuerySettings($querySettings);
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
    public function getCategoryByIdentifier($identifier)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }
    public function getAllCategoriesFromAPI($lang)
    {
        $json = file_get_contents("http://cookieapi.coding-freaks.com/?type=categories&lang=".$lang);
        $services = json_decode($json, true);
        return $services;
    }
    public function insertFromAPI($lang)
    {
        $categories = $this->getAllCategoriesFromAPI($lang);

        //TODO Error handling
        foreach ($categories as $category) {
            $categoryModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories();
            $categoryModel->setTitle($category["title"]);
            $categoryModel->setIdentifier($category["identifier"]);
            if (!empty($category["description"])) {
                $categoryModel->setDescription($category["description"]);
            }
            if (!empty($category["is_required"])) {
                $categoryModel->setIsRequired((int)$category["is_required"]);
            }
            $categoryDB = $this->getCategoryByIdentifier($category["identifier"]);
            if (count($categoryDB) == 0) {
                $this->add($categoryModel);
                $this->persistenceManager->persistAll();
            }
        }
    }
}
