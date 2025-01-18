<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
 * The repository for Cookies
 */
class CookieRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected CookieServiceRepository $cookieServiceRepository;


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
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    public function getCookieBySysLanguage($storage, $langUid = 0)
    {
        $query = $this->createQuery();

        if ($langUid !== false) {
            $languageAspect = new LanguageAspect((int)$langUid, (int)$langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
            $query->getQuerySettings()->setLanguageAspect($languageAspect);
            $query->getQuerySettings()->setStoragePageIds($storage);
        }

        $query->getQuerySettings()->setIgnoreEnableFields(false)->setStoragePageIds($storage);
        $cookies = $query->execute();
        $allCookies = [];
        foreach ($cookies as $category) {
            $allCookies[] = $category;
        }
        return $allCookies;
    }

    /**
     * @param $identifier
     */
    public function getCookieByName($identifier, $langUid = 0, $storage = [1])
    {
        $query = $this->createQuery();
        $languageAspect = new LanguageAspect($langUid, $langUid, LanguageAspect::OVERLAYS_ON); //$languageAspect->getOverlayType());
        $query->getQuerySettings()->setLanguageAspect($languageAspect);
        $query->getQuerySettings()->setStoragePageIds($storage);
        $query->matching($query->logicalAnd($query->equals('name', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }

    public function insertFromAPI($langConfiguration,$offline = false)
    {
        foreach ($langConfiguration as $lang_config) {
            if (empty($lang_config)) {
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {

                if(!$offline){
                    $cookies = $this->apiRepository->callAPI($lang["langCode"],"cookie");
                }else{
                    //offline call file
                    $cookies = $this->apiRepository->callFile($lang["langCode"],"cookie");
                }

                if(empty($cookies)){
                    return false;
                }

                $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
                foreach ($cookies as $cookie) {
                    if (empty($cookie["name"]) || empty($cookie["service_identifier"])) {
                        continue;
                    }
                    $cookieModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
                    $cookieModel->setPid($lang["rootSite"]);
                    $cookieModel->setName($cookie["name"]);
                    $cookieModel->setHttpOnly((int)$cookie["http_only"]);
                    if (!empty($cookie["path"])) {
                        $cookieModel->setPath($cookie["path"]);
                    }
                    if (!empty($cookie["secure"])) {
                        $cookieModel->setSecure((int)$cookie["secure"]);
                    }
                    if (!empty($cookie["is_regex"])) {
                        $cookieModel->setIsRegex(true);
                    }
                    $cookieModel->setServiceIdentifier($cookie["service_identifier"]);
                    if (!empty($cookie["description"])) {
                        $cookieModel->setDescription($cookie["description"]);
                    } else {
                        $cookieModel->setDescription("");
                    }
                    //$cookieDB = $this->getCookieByName($cookie["name"]);
                    $cookieDB = $this->getCookieByName($cookie["name"], 0, [$lang["rootSite"]]); // $lang_config["languageId"]
                    if (count($cookieDB) == 0) {
                        $this->add($cookieModel);
                        $this->persistenceManager->persistAll();
                        $cookieUID = $cookieModel->getUid();

                        //If Cookie is needed by other Service create mm Table
                        $service = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"], $lang["language"]["languageId"], [$lang["rootSite"]]);
                        if (!empty($service[0]) && $lang["language"]["languageId"] == 0) {
                            $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $service[0]->getUid() . "," . $cookieUID . ",0,0)";
                            $results = $con->executeQuery($sqlStr);
                        }
                    }

                    if($lang["language"]["languageId"] != 0){
                        $cookieDBOrigin = $this->getCookieByName($cookie["name"],0,[$lang["rootSite"]]); // $lang_config["languageId"]
                        $allreadyTranslated = $this->getCookieByName($cookie["name"],$lang["language"]["languageId"],[$lang["rootSite"]]);
                        if (count($allreadyTranslated) == 0) {
                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookie');
                            $queryBuilder->insert('tx_cfcookiemanager_domain_model_cookie')->values([
                                'pid' => $lang["rootSite"],
                                'sys_language_uid' => $lang["language"]["languageId"],
                                'l10n_parent' => (int)$cookieDBOrigin[0]->getUid(),
                                'name' => $cookie["name"],
                                'http_only' => (int)$cookie["http_only"],
                                'path' => empty($cookie["path"]) ? "/" : $cookie["path"],
                                'secure' => empty($cookie["secure"]) ? 0 : $cookie["secure"],
                                'is_regex' => empty($cookie["is_regex"]) ? 0 : $cookie["is_regex"],
                                'service_identifier' => empty($cookie["service_identifier"]) ? "unknown" : $cookie["service_identifier"],
                                'description' =>  empty($cookie["description"]) ? "" : $cookie["description"],
                            ])
                                ->executeStatement();
                        }

                        // * Get all Languages from a Service and create MM Table
                        $serviceTranslated = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"],  $lang["language"]["languageId"], [$lang["rootSite"]]);
                        if (!empty($serviceTranslated[0])) {
                            $suid = $serviceTranslated[0]->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                            if (!empty($suid)) {
                                // Check if the record already exists
                                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_cookieservice_cookie_mm');
                                $existingRecord = $queryBuilder
                                    ->select('uid_local', 'uid_foreign')
                                    ->from('tx_cfcookiemanager_cookieservice_cookie_mm')
                                    ->where(
                                        $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($suid)),
                                        $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($cookieDBOrigin[0]->getUid()))
                                    )
                                    ->executeQuery()
                                    ->fetchAssociative();

                                if (!$existingRecord) {
                                    // Insert the new record if it does not exist
                                    $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm (uid_local, uid_foreign, sorting, sorting_foreign) VALUES (" . $suid . "," . $cookieDBOrigin[0]->getUid() . ",0,0)";
                                    $results = $con->executeQuery($sqlStr);
                                }
                            } else {
                                // Handle the case where $suid is empty
                                // You could throw an exception, return an error, or log the issue, depending on your application's requirements
                            }
                        }

                    }
                }
            }
        }

        return true;
    }
}
