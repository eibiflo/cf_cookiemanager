<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;

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
    protected $cookieServiceRepository = null;

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }
    public function getAllCookiesFromAPI()
    {
        $json = file_get_contents("http://cookieapi.coding-freaks.com/?type=cookies");
        $cookies = json_decode($json, true);
        return $cookies;
    }

    /**
     * @param $identifier
     */
    public function getCookieByName($identifier)
    {
        $query = $this->createQuery();
        $query->matching($query->logicalAnd($query->equals('name', $identifier)));
        $query->setOrderings(array("crdate" => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING))->setLimit(1);
        return $query->execute();
    }
    public function insertFromAPI($lang)
    {
        $cookies = $this->getAllCookiesFromAPI();
        foreach ($cookies as $cookie) {
            if(empty($cookie["name"])){
                continue;
            }
            $cookieModel = new \CodingFreaks\CfCookiemanager\Domain\Model\Cookie();
            $cookieModel->setName($cookie["name"]);
            $cookieModel->setHttpOnly((int) $cookie["http_only"]);
            if(!empty($cookie["path"])){
                $cookieModel->setPath($cookie["path"]);
            }
            if(!empty($cookie["secure"])){
                $cookieModel->setSecure($cookie["secure"]);
            }
            if(str_contains($cookie["name"],"*")){
                $cookieModel->setIsRegex(true);
            }
            $cookieModel->setServiceIdentifier($cookie["service_identifier"]);
            if (!empty($service["description"])) {
                $cookieModel->setDescription($cookie["description"]);
            }else{
                $cookieModel->setDescription("");
            }
            $cookieDB = $this->getCookieByName($cookie["name"]);
            if (count($cookieDB) == 0) {
                $this->add($cookieModel);
                $this->persistenceManager->persistAll();
                $cookieUID = $cookieModel->getUid();
                $service = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"]);
                if (!empty($service[0])) {
                    $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
                    $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $service[0]->getUid() . "," . $cookieUID . ",0,0)";
                    $results = $con->executeQuery($sqlStr);
                    $serviceTranslated = $this->cookieServiceRepository->getServiceByIdentifier($cookie["service_identifier"],1);
                    if(!empty($serviceTranslated[0])){
                        //For Multi Language
                        $sqlStr = "INSERT INTO tx_cfcookiemanager_cookieservice_cookie_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $serviceTranslated[0]->getUid() . "," . $cookieUID . ",0,0)";
                        $results = $con->executeQuery($sqlStr);
                    }
                }
            } else {
                $cookieUID = $cookieDB[0]->getUid();
            }
        }
    }
}
