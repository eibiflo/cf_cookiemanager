<?php


namespace  CodingFreaks\CfCookiemanager\Utility;


use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class HelperUtility
{

    /**
     * Returns a list of category fields for a given table for populating selector "category_field"
     * in tt_content table (called as itemsProcFunc).
     *
     * @param array $configuration Current field configuration
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getTcaTypes(array &$configuration){
        foreach ($GLOBALS["TCA"]["tt_content"]["columns"]["CType"]["config"]["items"] as $key => $type ){
            $lable = LocalizationUtility::translate($type["0"], $type["3"]);
            //$configurationIdentifier = $type["1"].$type["2"].$type["3"];
            $configurationIdentifier = $type["1"];
           // $configuration['items'][] = [$lable,md5($configurationIdentifier)];
            $configuration['items'][] = [$lable,$configurationIdentifier];
        }
    }

    /**
     * TODO Returns a list of all Variables used in fields for a given service
     *
     *
     * @param array $configuration Current field configuration
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getVariablesFromConfig(array &$configuration){
        //DebuggerUtility::var_dump($configuration);
    }

    /**
     * Returns a list Typo3 ConnectionPool Object for Custom Querys
     * Means this Returns the Database Driver
     *
     * @return ConnectionPool
     */
    public static function getDatabase() : \TYPO3\CMS\Core\Database\Connection {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class);
        $con = $connection->getConnectionByName("Default");
        return $con;
    }


    /* TODO Make a own NodeType and do this in Javascript */
    public function itemsProcFunc(&$params): void
    {
        $db = self::getDatabase();
        $result = $db->createQueryBuilder()->select("uid","identifier","name","category_suggestion")->from('tx_cfcookiemanager_domain_model_cookieservice')->executeQuery();
        $mapper = [];
        while ($row = $result->fetchAssociative()) {
            // Do something with that single row
            $mapper[$row["uid"]] = [
              "category_suggestion" =>  $row["category_suggestion"],
              "name" =>  $row["name"]." ".$row["category_suggestion"],
            ];
        }

        foreach ($params['items'] as &$item){
            $tmpData = $mapper[$item[1]];
            //$item[0] = $item[0]." | ". $tmpData["category_suggestion"];
            $item[3] = $tmpData["category_suggestion"];
        }

    }


    public static function getCookieServicesFilteritemGroups(){
        try{
            $db = self::getDatabase();
            $result = $db->createQueryBuilder()->select("identifier","title")->from('tx_cfcookiemanager_domain_model_cookiecartegories')->executeQuery();
            $filter = [];
            while ($row = $result->fetchAssociative()) {
                $filter[$row["identifier"]] = $row["title"];
            }
            return $filter;
        }catch (\Doctrine\DBAL\Exception\TableNotFoundException $exception){
            return false;
        }
    }

    public static function getCookieServicesMultiSelectFilterItems(){
        try{
            $db = self::getDatabase();
            $result = $db->createQueryBuilder()->select("identifier","title")->from('tx_cfcookiemanager_domain_model_cookiecartegories')->executeQuery();
            $filter = [
                [" ","All"]
            ];

            while ($row = $result->fetchAssociative()) {
                $filter[$row["identifier"]] = [$row["identifier"], $row["title"]];
            }

            return $filter;
        }catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex){
            return false;
        }
    }




}
