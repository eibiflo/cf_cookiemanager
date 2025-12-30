<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\DebugUtility;
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
 * The repository for CookieServices
 */
class CookieServiceRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    public function getServicesBySysLanguage($storage, $langUid = 0)
    {
        $query = $this->createQuery();

        if ($langUid !== false) {
            $languageAspect = new LanguageAspect((int)$langUid, (int)$langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
            $query->getQuerySettings()->setLanguageAspect($languageAspect);
            $query->getQuerySettings()->setStoragePageIds($storage);
        }

        $query->getQuerySettings()->setIgnoreEnableFields(false)->setStoragePageIds($storage);
        $cookieServices = $query->execute();
        $allCookieServices = [];
        foreach ($cookieServices as $service) {
            $allCookieServices[] = $service;
        }
        return $allCookieServices;
    }


    /**
     * Returns all Services from CodingFreaks CookieManager
     *
     * @param array $allCategorys
     * @throws \UnexpectedValueException
     * @return array
     */
    public function getAllServices($storageUID)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true)->setStoragePageIds([$storageUID]);
        $cookieCartegories = $query->execute();
        $allServices = [];
        $wrongServices = [];
        foreach ($cookieCartegories as $service) {
            $categoryTemp = [];
            //Find Variables
            preg_match_all('/\[##(.*?)##\]/', $service->getOptInCode(), $matches);
            if(!empty($matches[0])){
                $variablesNeeded = count($matches[0]);
                $variablesUsed = count($service->getVariablePriovider());
                if($variablesNeeded > $variablesUsed){
                    $wrongServices[$service->getIdentifier()] = $service;
                }
            }


            $categoryTemp["service"] = $service;
            $categoryTemp["service_wrong"] = $wrongServices;
            $allServices[] = $categoryTemp;
        }
        return $allServices;
    }

    /**
     * Find a Service by its identifier and language UID in Storage (PID)
     * @param $identifier
     */
    public function getServiceByIdentifier($identifier,$langUid = 0,$storage=[1])
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
     * Retrieves the language overlay for a given cookie service.
     *
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $service The cookie service for which the language overlay is to be retrieved.
     * @param int $langUid The language UID for which the overlay is to be fetched. If 0, the original service is returned.
     * @return \CodingFreaks\CfCookiemanager\Domain\Model\Cookie The language overlay of the service, or the original service if no overlay is found.
     */
    public function getCookiesLanguageOverlay($service,$langUid){
        if($langUid == 0){
            return $service;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("tx_cfcookiemanager_domain_model_cookie");
        $query = $queryBuilder->select('*')
            ->from("tx_cfcookiemanager_domain_model_cookie")
            ->where($queryBuilder->expr()->eq('sys_language_uid', $langUid))
            ->andWhere($queryBuilder->expr()->eq('l10n_parent', $service->getUid()))
            ->executeQuery();

        $dataMapper = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class);
        $overlay = $dataMapper->map(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie::class, $query->fetchAllAssociative());
        if(empty($overlay[0])){
            return $service;
        }

        return $overlay[0];
    }


}
