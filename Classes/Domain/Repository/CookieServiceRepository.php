<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

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
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieCartegoriesRepository = null;

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository
     */
    public function injectCookieCartegoriesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository)
    {
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
    }

    /**
     * Returns all Services from CodingFreaks CookieManager
     *
     * @param array $allCategorys
     * @throws \UnexpectedValueException
     * @return array
     */
    public function getAllServices()
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
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
    public function getServiceByIdentifier($identifier,$langUid = 0)
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setLanguageUid($langUid);
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

    public function getAllServicesFromAPI($lang)
    {
        $json = file_get_contents("https://cookieapi.coding-freaks.com/api/services/".$lang);
        $services = json_decode($json, true);
        return $services;
    }

    public function insertFromAPI($lang)
    {
        foreach ($lang as $lang_config){
            $services = $this->getAllServicesFromAPI($lang_config["iso-639-1"]);
            foreach ($services as $service) {
                $servicesModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
                if(empty($service["identifier"])){
                    continue;
                }
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
                $serviceDB = $this->getServiceByIdentifier($service["identifier"]);
                if (count($serviceDB) == 0) {
                    $this->add($servicesModel);
                    $this->persistenceManager->persistAll();
                }

                if($lang_config["languageId"] != 0){
                    $categoryDB = $this->getServiceByIdentifier($service["identifier"],0); // $lang_config["languageId"]
                    $allreadyTranslated = $this->getServiceByIdentifier($service["identifier"],$lang_config["languageId"]);
                    if (count($allreadyTranslated) == 0) {
                        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookieservice');
                        $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookieservice')->values([
                                'pid' =>1,
                                'sys_language_uid' => $lang_config["languageId"],
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
                            ->execute();
                    }
                }
            }



        }


    }
}
