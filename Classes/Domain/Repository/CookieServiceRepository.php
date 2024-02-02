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

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository
     */
    private ApiRepository $apiRepository;

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository
     */
    public function injectApiRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository $apiRepository)
    {
        $this->apiRepository = $apiRepository;
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
     * @param $suggestion
     */
    public function getServiceBySuggestion($suggestion,$langUid = 0)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid);
        $query->matching($query->logicalAnd($query->equals('category_suggestion', $suggestion)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING));
        return $query->execute();
    }

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

    public function insertFromAPI($lang,$offline = false)
    {
        foreach ($lang as $lang_config){
            if(empty($lang_config)){
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {

                if(!$offline){
                    $services = $this->apiRepository->callAPI($lang["langCode"],"services");
                }else{
                    //offline call file
                    $services = $this->apiRepository->callFile($lang["langCode"],"services");
                }

                if(empty($services)){
                    return false;
                }

                foreach ($services as $service) {
                    $servicesModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
                    if(empty($service["identifier"])){
                        continue;
                    }
                    $servicesModel->setPid($lang["rootSite"]);
                    $servicesModel->setName($service["name"]);
                    $servicesModel->setIdentifier($service["identifier"]);
                    $servicesModel->setDescription($service["description"] ?? "");
                    $servicesModel->setProvider($service["provider"] ?? "");
                    $servicesModel->setOptInCode($service["opt_in_code"] ?? "");
                    $servicesModel->setOptOutCode($service["opt_out_code"] ?? "");
                    $servicesModel->setFallbackCode($service["fallback_code"] ?? "");
                    $servicesModel->setDsgvoLink($service["dsgvo_link"] ?? "");
                    $servicesModel->setIframeEmbedUrl($service["iframe_embed_url"] ?? "");
                    $servicesModel->setIframeThumbnailUrl($service["iframe_thumbnail_url"] ?? "");
                    $servicesModel->setIframeNotice($service["iframe_notice"] ?? "");
                    $servicesModel->setIframeLoadBtn($service["iframe_load_btn"] ?? "");
                    $servicesModel->setIframeLoadAllBtn($service["iframe_load_all_btn"] ?? "");
                    $servicesModel->setCategorySuggestion($service["category_suggestion"] ?? "");
                    $serviceDB = $this->getServiceByIdentifier($service["identifier"],0,[$lang["rootSite"]]);
                    if (count($serviceDB) == 0) {
                        $this->add($servicesModel);
                        $this->persistenceManager->persistAll();
                    }

                    if($lang["language"]["languageId"] != 0){
                        $categoryDB = $this->getServiceByIdentifier($service["identifier"],0,[$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getServiceByIdentifier($service["identifier"],$lang["language"]["languageId"],[$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookieservice');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookieservice')->values([
                                    'pid' => $lang["rootSite"],
                                    'sys_language_uid' => $lang["language"]["languageId"],
                                    'l10n_parent' => (int)$categoryDB[0]->getUid(),
                                    'name' =>$service["name"],
                                    'identifier' =>$service["identifier"],
                                    'description' =>$service["description"],
                                    'provider' =>$servicesModel->getProvider(),
                                    'opt_in_code' =>$servicesModel->getOptInCode(),
                                    'opt_out_code' =>$servicesModel->getOptOutCode(),
                                    'fallback_code' =>$servicesModel->getFallbackCode(),
                                    'dsgvo_link' =>$servicesModel->getDsgvoLink(),
                                    'iframe_embed_url' =>$servicesModel->getIframeEmbedUrl(),
                                    'iframe_thumbnail_url' => $servicesModel->getIframeThumbnailUrl(),
                                    'iframe_notice' =>$servicesModel->getIframeNotice(),
                                    'iframe_load_btn' =>$servicesModel->getIframeLoadBtn(),
                                    'iframe_load_all_btn' =>$servicesModel->getIframeLoadAllBtn(),
                                    'category_suggestion' =>$servicesModel->getCategorySuggestion(),
                                ])
                                ->executeStatement();
                        }
                    }
                }
            }



        }

        return true;
    }
}
