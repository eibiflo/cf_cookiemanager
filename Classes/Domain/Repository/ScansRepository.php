<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;


use TYPO3\CMS\Core\Site\SiteFinder;
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
            $provider = json_decode($scan->getProvider(),true);
            if(!empty($provider)){
                $foundProvider = count($provider);
            }
            $scan->foundProvider = $foundProvider;
            $preparedScans[] = $scan->_getProperties();
        }
        return $preparedScans;
    }

    public function doExternalScan($target,&$error = false)
    {
        //The data you want to send via POST
        $fields = ['target' => $target, "clickConsent" => base64_encode('//*[@id="c-p-bn"]') , "limit"=> 10];
        //open connection
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://cookieapi.coding-freaks.com/api/scan');
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

    public function findByIdent($identifier){
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('identifier', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute()[0];
    }

    public function updateScan($identifier)
    {
        $json = file_get_contents("https://cookieapi.coding-freaks.com/api/scan/" . $identifier);
        $report = json_decode($json,true);
        $test = $this->findByIdent($identifier);
        $test->setStatus($report["status"]);
        if(!empty($report["target"])){
            $test->setDomain($report["target"]);
        }
        if($report["status"] == "done"){
            $test->setProvider(json_encode($report["provider"]));
            $test->setUnknownProvider(json_encode($report["unknownprovider"]));
            $test->setCookies(json_encode($report["cookies"]));
            $test->setScannedSites((string)$report["scannedSites"]);
        }
        $this->update($test);
    }

    public function autoconfigure($identifier,$language = 0,$storageUID)
    {
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $categories = $this->cookieCartegoriesRepository->getAllCategories([$storageUID],$language);
        $json = file_get_contents("https://cookieapi.coding-freaks.com/api/scan/" . $identifier);

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
    }

}
