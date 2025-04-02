<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;


use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
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
            $languageAspect = new LanguageAspect((int)$language, (int)$language, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
            $querysettings->setLanguageAspect($languageAspect);
        }
        $querysettings->setStoragePageIds($storage);
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING));
        $scans =  $query->execute();
        $preparedScans = [];
        foreach ($scans as $scan){
            $foundProvider = 0;
            $unknownProvider = "";
            $provider = json_decode($scan->getProvider(),true);
            if(!empty($provider["unknown"])){
                $unknownProvider = $provider["unknown"];
            }
            if(!empty($provider)){
                $foundProvider = count($provider);
            }
            $scan->setFoundProvider($foundProvider);
            $scan->setUnknownProvider($unknownProvider);
            $preparedScans[] = $scan->_getProperties();
        }
        return $preparedScans;
    }

    public function doExternalScan($requestArguments,$fullTypoScript,&$error = false)
    {
        if(empty( $requestArguments["target"]) || empty( $requestArguments["limit"])){
            $error = "Please enter a scan target and scan limit";
            return false;
        }

        if($fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']["scan_api_key"] == "scantoken"){
            $apiKey = "";
        }else{
            $apiKey = $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']["scan_api_key"];
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
        curl_setopt($ch, CURLOPT_URL,  $fullTypoScript['plugin.']['tx_cfcookiemanager_cookiefrontend.']['frontend.']["end_point"].'scan');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        if($result === false){
            $error = "Error: " . curl_error($ch);
            return false;
        }

        // Check if the result is valid JSON
        $scanIdentifier = json_decode($result, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $error = "API Error: Invalid response format, please report this issue";
            return false;
        }

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
}
