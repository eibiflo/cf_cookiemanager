<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

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
    public function getAllServices($request)
    {
        $backendUriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $cookieCartegories = $this->findAll();
        $allServices = [];
        $wrongServices = [];
        foreach ($cookieCartegories as $service) {
            $uriParameters = ['edit' => ['tx_cfcookiemanager_domain_model_cookieservice' => [$service->getUid() => 'edit']], "returnUrl" => urldecode($request->getAttribute('normalizedParams')->getRequestUri())];
            $categoryTemp = [];
            $categoryTemp["linkEdit"] = $backendUriBuilder->buildUriFromRoute('record_edit', $uriParameters);

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
    public function getServiceByIdentifier($identifier)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    public function getAllServicesFromAPI($lang)
    {
        $json = file_get_contents("http://cookieapi.coding-freaks.com/?type=services&lang=".$lang);
        $services = json_decode($json, true);
        return $services;
    }

    public function insertFromAPI($lang)
    {
        $services = $this->getAllServicesFromAPI($lang);
        foreach ($services as $service) {
            $servicesModel = new \CodingFreaks\CfCookiemanager\Domain\Model\CookieService();
            $servicesModel->setName($service["name"]);
            $servicesModel->setIdentifier($service["identifier"]);
            if (!empty($service["description"])) {
                $servicesModel->setDescription($service["description"]);
            }
            if (!empty($service["provider"])) {
                $servicesModel->setProvider($service["provider"]);
            }
            if (!empty($service["opt_in_code"])) {
                $servicesModel->setOptInCode($service["opt_in_code"]);
            }
            if (!empty($service["opt_out_code"])) {
                $servicesModel->setOptOutCode($service["opt_out_code"]);
            }
            if (!empty($service["fallback_code"])) {
                $servicesModel->setFallbackCode($service["fallback_code"]);
            }
            if (!empty($service["dsgvo_link"])) {
                $servicesModel->setDsgvoLink($service["dsgvo_link"]);
            }
            if (!empty($service["iframe_embed_url"])) {
                $servicesModel->setIframeEmbedUrl($service["iframe_embed_url"]);
            }
            if (!empty($service["iframe_thumbnail_url"])) {
                $servicesModel->setIframeThumbnailUrl($service["iframe_thumbnail_url"]);
            }
            if (!empty($service["iframe_notice"])) {
                $servicesModel->setIframeNotice($service["iframe_notice"]);
            }
            if (!empty($service["iframe_load_btn"])) {
                $servicesModel->setIframeLoadBtn($service["iframe_load_btn"]);
            }
            if (!empty($service["iframe_load_all_btn"])) {
                $servicesModel->setIframeLoadAllBtn($service["iframe_load_all_btn"]);
            }
            if (!empty($service["category"])) {
                $servicesModel->setCategorySuggestion($service["category"]);
            }
            $serviceDB = $this->getServiceByIdentifier($service["identifier"]);
            if (count($serviceDB) == 0) {
                $this->add($servicesModel);
                $this->persistenceManager->persistAll();
                $serviceDBUID = $servicesModel->getUid();
            } else {
                $serviceDBUID = $serviceDB[0]->getUid();
            }

            /*
            $category = $this->cookieCartegoriesRepository->getCategoryByIdentifier($service["category"]);
            if (count($category) != 0) {
                $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
                $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $category[0]->getUid() . "," . $serviceDBUID . ",0,0)";
                $results = $con->executeQuery($sqlStr);
            }
            */
        }
    }
}
