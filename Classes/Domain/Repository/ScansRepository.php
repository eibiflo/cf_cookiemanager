<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;


use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 Florian Eibisberger, CodingFreaks
 */

/**
 * The repository for Scans
 */
class ScansRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
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
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieServiceRepository = null;

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
        $querySettings = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    public function getLatest()
    {
        $query = $this->createQuery();
        $query->setOrderings(array("uid" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING))->setLimit(1);
        return $query->execute()[0];
    }


    public function getTarget($storage){
        $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
        $target = "";
        foreach ($sites as $rootsite) {
            if($rootsite->getRootPageId() == $storage){
                $target = $rootsite->getBase()->__toString();
            }
        }
        return $target;
    }


    /**
     * Fetch Scan Information from Database
     *
     * @param $storage
     * @param $language
     * @return array
     */
    public function getScansForStorageAndLanguage($storage, $language) : array{
        $query = $this->createQuery();
        $querysettings = $query->getQuerySettings();
        if($language == false){
            $querysettings->setRespectSysLanguage(false);
        }else{
            $querysettings->setLanguageUid($language);
        }
        $querysettings->setStoragePageIds($storage);
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING));
        $scans =  $query->execute();
        $preparedScans = [];
        foreach ($scans as $scan){
            $foundProvider = 0;
            $unknownProvider = 0;
            $provider = json_decode($scan->getProvider(),true);
            if(!empty($provider["unknown"])){
                $unknownProvider = $provider["unknown"];
            }
            if(!empty($provider)){
                $foundProvider = count($provider);
            }
            $scan->foundProvider = $foundProvider;
            $scan->unknownProvider = $unknownProvider;
            $preparedScans[] = $scan->_getProperties();
        }
        return $preparedScans;
    }

    public function doExternalScan($requestArguments,&$error = false)
    {
        if(empty( $requestArguments["target"]) || empty( $requestArguments["limit"])){
            $error = "Please enter a scan target and scan limit";
            return false;
        }


        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        if($extensionConfiguration["scanApiKey"] == "scantoken"){
            $apiKey = "";
        }else{
            $apiKey = $extensionConfiguration["scanApiKey"];
        }

        if(!empty($requestArguments["disable-consent-optin"])){
            $xpath = "ZmFsc2U=";
        }else{
            $xpath = base64_encode('//*[@id="c-p-bn"]');
        }

        //The data you want to send via POST
        $fields = ['target' => $requestArguments["target"], "clickConsent" => $xpath, "limit"=> $requestArguments["limit"], "apiKey" => $apiKey];

        if(!empty($requestArguments["ngrok-skip"])){
            $fields["ngrok-skip"] = true;
        }




        //open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $extensionConfiguration["endPoint"].'scan');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        $scanIdentifier = json_decode($result, true);

        if (empty($scanIdentifier["identifier"])) {
            $error = $scanIdentifier["error"];
            return false;
        }

        $scanid = $scanIdentifier["identifier"];

        return $scanid;
    }


    /**
     * Find a single object by a specific property.
     *
     * @param string $propertyName
     * @param mixed  $propertyValue
     * @return mixed|null
     */
    public function findOneByProperty($propertyName, $propertyValue)
    {
        $query = $this->createQuery();
        $query->matching($query->equals($propertyName, $propertyValue));
        $query->setLimit(1);

        return $query->execute()->getFirst();
    }


    public function findByIdentCf($identifier){
        return $this->findOneByProperty('identifier' , $identifier);
        //Dose not work in v11?       return $this->findOneBy(['identifier' => $identifier]);
    }

    public function updateScan($identifier)
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $json = file_get_contents($extensionConfiguration["endPoint"]."scan/" . $identifier);
        if(empty($json)){
            return false;
        }
        $report = json_decode($json,true);
        $test = $this->findByIdentCf($identifier);
        $test->setStatus($report["status"]);
        if(!empty($report["target"])){
            $test->setDomain($report["target"]);
        }
        if($report["status"] == "done"){
            $test->setProvider(json_encode($report["provider"]));
            $test->setUnknownProvider("[]");
            $test->setCookies("[]");
            $test->setScannedSites((string)$report["scannedSites"]);
        }
        $this->update($test);
    }

    public function autoconfigureImport($arguments,$storageUID,$language = 0){
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $scan = $this->findByIdentCf($arguments["identifier"]);
        $providerArray = json_decode($scan->getProvider(),true);
        foreach ($providerArray as $service_provider => $service){

            $service_db = $this->cookieServiceRepository->getServiceByIdentifier($service_provider,$language);
            if(empty($service_db[0])){
                //Add log if not found Updated Task is needed, TODO make a configuration for API Import
                continue;
            }
            if(!empty($arguments["importType-" . $service_provider]) && !empty($arguments["category-" . $service_provider])){
                    if($arguments["importType-" . $service_provider] == "ignore") {
                            continue;
                    }

                    //Check if exists
                    $allreadyExists = false;
                     $category = $this->cookieCartegoriesRepository->getCategoryByIdentifier($service["information"]["category_suggestion"],$language);
                    if(empty($category[0])){
                        //Add log if not found Updated Task is needed, TODO make a configuration for API Import
                        continue;
                    }
                    foreach ($category[0]->getCookieServices()->toArray() as $currentlySelected) {
                        //TODO Import type check and category override
                        if ($currentlySelected->getIdentifier() == $service_db[0]->getIdentifier()) {
                            $allreadyExists = true;
                        }
                    }

                    if($arguments["importType-" . $service_provider] == "override") {
                        $this->cookieCartegoriesRepository->removeServiceFromCategory($category[0], $service_db[0]);
                        $allreadyExists = false;
                    }
                    if (!$allreadyExists) {


                        if(!empty($arguments["category-" . $service_provider]) && $arguments["category-" . $service_provider] !==  $category[0]->getIdentifier()) {
                            $category = $this->cookieCartegoriesRepository->getCategoryByIdentifier($arguments["category-" . $service_provider],$language);
                        }

                        $cuid =  $category[0]->getUid();
                        $suid = $service_db[0]->getUid();
                        if ($language !== 0) {
                            $cuid = $category[0]->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                        }
                        if ($language !== 0) {
                            $suid = $service_db[0]->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                        }

                        $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $cuid . "," . $suid . ",0,0)";
                        $results = $con->executeQuery($sqlStr);
                        $this->persistenceManager->persistAll();
                    }


            }
        }


/*

        die();
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $categories = $this->cookieCartegoriesRepository->getAllCategories([$storageUID],$language);
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $json = file_get_contents($extensionConfiguration["endPoint"]."scan/" . $identifier);

        if (!empty($json)) {
            $report = json_decode($json, true);
            if ($report["status"] === "done") {
                foreach ($categories as $category) {
                    $services = $this->cookieServiceRepository->getServiceBySuggestion($category->getIdentifier(),$language);
                    foreach ($services as $service) {
                        if (empty($report["provider"][$service->getIdentifier()])) {
                            continue;
                        }
                        //Check if exists
                        $allreadyExists = false;
                        foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
                            if ($currentlySelected->getIdentifier() == $service->getIdentifier()) {
                                $allreadyExists = true;
                            }
                        }
                        if (!$allreadyExists) {
                            $cuid =  $category->getUid();
                            $suid = $service->getUid();
                            if ($language !== 0) {
                                $cuid = $category->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            }
                            if ($language !== 0) {
                                $suid = $service->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            }

                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $cuid . "," . $suid . ",0,0)";
                            $results = $con->executeQuery($sqlStr);
                        }
                    }
                }

                $this->persistenceManager->persistAll();
                return $report;
            } else if (!empty($report["status"])) {
                return $report;
            }
        } else {
            //No Scan found, or network error!
            $return = false;
        }

        return $return;

*/
    }

    //TODO get Saved Scan and save user Report changes in Database for Import function
    public function autoconfigure($identifier,$storageUID,$language = 0)
    {
        $newConfiguration = [];
        $newConfiguration["categories"] = $this->cookieCartegoriesRepository->getAllCategories([$storageUID],$language);

        $scan = $this->findByIdentCf($identifier);
    //    DebuggerUtility::var_dump($scan);
     //   DebuggerUtility::var_dump($scan);
        $newConfiguration["scan"] = $scan;
        //$extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
       // $json = file_get_contents($extensionConfiguration["endPoint"]."scan/" . $identifier);
 //       DebuggerUtility::var_dump($identifier);
//die("OK");
        if (!empty($scan)) {
            $report = $scan;

            if ($report->getStatus() === "done") {

                if(empty($report->getProvider())){
                    return false;
                }

                $providerArray = json_decode($report->getProvider(),true);
                foreach ($providerArray as $service_provider => $service){

                   // DebuggerUtility::var_dump($service_provider);
                    $newConfiguration["services"][$service_provider] = $service;
                    //   $services = $this->cookieServiceRepository->getServiceBySuggestion($category->getIdentifier(),$language);
                    /*
                    foreach ($services as $service) {
                        if (empty($report["provider"][$service->getIdentifier()])) {
                            continue;
                        }
                        //Check if exists
                        $allreadyExists = false;
                        foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
                            if ($currentlySelected->getIdentifier() == $service->getIdentifier()) {
                                $allreadyExists = true;
                            }
                        }
                        if (!$allreadyExists) {
                            $cuid =  $category->getUid();
                            $suid = $service->getUid();
                            if ($language !== 0) {
                                $cuid = $category->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            }
                            if ($language !== 0) {
                                $suid = $service->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            }

                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $cuid . "," . $suid . ",0,0)";
                            $results = $con->executeQuery($sqlStr);

                        }

                    }
                    */

                }

                $this->persistenceManager->persistAll();
                return $newConfiguration;
            }
        }

        return false;
    }

}
