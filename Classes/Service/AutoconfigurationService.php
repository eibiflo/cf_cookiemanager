<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Service\Scan\ScanService;
use CodingFreaks\CfCookiemanager\Service\Sync\ApiClientInterface;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/**
 * Service for handling autoconfiguration of cookie services based on scan results.
 */
class AutoconfigurationService
{
    public function __construct(
        private readonly ScansRepository $scansRepository,
        private readonly PersistenceManager $persistenceManager,
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly CookieServiceRepository $cookieServiceRepository,
        private readonly ApiClientInterface $apiClientService,
        private readonly ScanService $scanService,
    ) {}

    /**
     * @param $identifier
     * @param $storageUID
     * @param int $language
     * @return array|bool
     */
    public function autoconfigure($identifier,$storageUID,$language = 0)
    {
        $newConfiguration = [];
        $newConfiguration["categories"] = $this->cookieCartegoriesRepository->getAllCategories([$storageUID],$language);

        $scan = $this->scansRepository->findByIdentCf($identifier);

        $newConfiguration["scan"] = $scan;

        if (!empty($scan)) {
            $report = $scan;

            if ($report->getStatus() === "done") {

                if(empty($report->getProvider())){
                    return false;
                }

                $providerArray = json_decode($report->getProvider(),true);
                foreach ($providerArray as $service_provider => $service){
                    $newConfiguration["services"][$service_provider] = $service;
                }

                $this->persistenceManager->persistAll();
                return $newConfiguration;
            }
        }

        return false;
    }

    public function autoconfigureImport($arguments,$storageUID,$language = 0){
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
        $scan = $this->scansRepository->findByIdentCf($arguments["identifier"]);
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
    }

    public function updateScan($identifier, $cf_extensionTypoScript)
    {
        $resultArray = $this->apiClientService->fetchFromEndpoint('scan/' . $identifier, '', $cf_extensionTypoScript['end_point']);
        if(empty($resultArray)){
            return false;
        }
        $report = $resultArray;
        $test = $this->scansRepository->findByIdentCf($identifier);
        $test->setStatus($report["status"]);
        if(!empty($report["target"])){
            $test->setDomain($report["target"]);
        }

        if(!empty($report["provider"])){
            foreach ($report["provider"] as $index => $provider){
                unset($report["provider"][$index]["urls"]); //Remove URLS because of size
            }
        }

        if($report["status"] == "done"){
            $test->setProvider(json_encode($report["provider"]));
            $test->setUnknownProvider("[]");
            $test->setCookies("[]");
            $test->setScannedSites((string)$report["scannedSites"]);
        }
        $this->scansRepository->update($test);
    }


    /**
     * Handles the autoconfiguration request.
     *
     *
     * @return array
     */
    public function handleAutoConfiguration($storageUID,$configuration,$cf_extensionTypoScript){

        $messages = [];
        $assignToView = [];

        $languageID = $configuration["languageID"];
        if((int)$languageID != 0){
            $messages[] = ['Language Overlay Detected, please use the main language for scanning,', 'Language Overlay Detected', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::NOTICE];
        }

        $arguments = $configuration["arguments"];

        if (isset($arguments['autoconfiguration_form_configuration'])) {
            // Autoconfigure import button was clicked, so run autoconfiguration imports
            $this->autoconfigureImport($arguments,(int) $storageUID,$languageID);
            $messages[] = ['Autoconfiguration completed, refresh the current Page!', 'Autoconfiguration completed', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::OK];
        }

        // Handle autoconfiguration and scanning requests
        if(!empty($arguments["autoconfiguration"]) ){
            // Run autoconfiguration
            $result =$this->autoconfigure($arguments["identifier"],(int) $storageUID, $languageID);
            if($result !== false){
                $messages[] = ['Select override for deleting old references, to import new as selected. Select ignore, to skip the record.', 'AutoConfiguration overview', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::INFO];
            }

            $assignToView =[
                'autoconfiguration_render' => true,
                'autoconfiguration_result' => $result,
            ];

        }

        $newScan = false;
        if (!empty($arguments['target'])) {
            // Create new scan using ScanService
            $scanResult = $this->scanService->initiateExternalScan($arguments, $cf_extensionTypoScript);

            if ($scanResult->isSuccess()) {
                $scanModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Scans();
                $scanModel->setPid($storageUID);
                $scanModel->setIdentifier($scanResult->getIdentifier());
                $scanModel->setStatus('waitingQueue');
                $this->scansRepository->add($scanModel);
                $this->persistenceManager->persistAll();
                $newScan = true;
                $messages[] = ['New Scan started, this can take a some minutes..', 'Scan Started', ContextualFeedbackSeverity::OK];
            } else {
                $error = $scanResult->getError() ?: 'Unknown Error';
                $messages[] = [$error, 'Scan Error', ContextualFeedbackSeverity::ERROR];
            }
        }

        //Update Latest scan if status not done
        if($this->scansRepository->countAll() !== 0){
            $latestScan = $this->scansRepository->findAll();
            if(!empty($latestScan)){
                foreach ($latestScan as $scan){
                    if(($scan->getStatus() == "scanning" || $scan->getStatus() == "waitingQueue") && $scan->getStatus() != "error" && $scan->getStatus() != "done"){
                        $this->updateScan($scan->getIdentifier(),$cf_extensionTypoScript);
                    }
                }
            }
        }
        $this->persistenceManager->persistAll();

        return [
            'newScan' => $newScan,
            'messages' => $messages,
            'assignToView' => $assignToView,
        ];
    }
}