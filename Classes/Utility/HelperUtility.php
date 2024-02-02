<?php


namespace  CodingFreaks\CfCookiemanager\Utility;


use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\VariablesRepository;
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
     *
     * @param array $configuration Current field configuration
     * @throws \UnexpectedValueException
     * @internal
     */
    public function getVariablesFromItem(array &$configuration){
        $cookieServiceRepository = GeneralUtility::makeInstance(CookieServiceRepository::class);
        $db = self::getDatabase();
        $queryBuilder = $db->createQueryBuilder()->select("uid","identifier","cookieservice")->from('tx_cfcookiemanager_domain_model_variables');
        $result =  $queryBuilder->where(   $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($configuration["row"]["uid"],\Doctrine\DBAL\ParameterType::INTEGER)))->executeQuery()->fetchAssociative();
        $service = $cookieServiceRepository->findByUid($result["cookieservice"]);
        if(!empty($service)){
            $variables = $service->getUsedVariables();
            if(!empty($variables)){
                foreach (array_unique($variables) as $unknownVariable){
                    $configuration["items"][]  = [$unknownVariable,$unknownVariable];
                }
            }
        }
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
        $selectedLanguage = $params["row"]["sys_language_uid"];

        $db = self::getDatabase();
        $queryBuilder = $db->createQueryBuilder()->select("uid","identifier","name","category_suggestion","sys_language_uid")->from('tx_cfcookiemanager_domain_model_cookieservice');
        $result = $queryBuilder->where(
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($selectedLanguage,\Doctrine\DBAL\ParameterType::INTEGER)),
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter( $params["row"]["pid"],\Doctrine\DBAL\ParameterType::INTEGER))
        )->executeQuery();
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

    public function itemsProcFuncCookies(&$params): void
    {
        //Cookies are not Translated
        $db = self::getDatabase();
        $queryBuilder = $db->createQueryBuilder()->select("uid","name","service_identifier","sys_language_uid")->from('tx_cfcookiemanager_domain_model_cookie');
        $result = $queryBuilder->where(
            $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter(0,\Doctrine\DBAL\ParameterType::INTEGER)),
           // $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter( $params["row"]["pid"],\PDO::PARAM_INT))
        )->executeQuery();
        $mapper = [];

        while ($row = $result->fetchAssociative()) {
            // Do something with that single row
            $mapper[$row["uid"]] = [
              "service_identifier" =>  $row["service_identifier"],
              "name" =>  $row["name"]." ".$row["service_identifier"],
            ];
        }

        foreach ($params['items'] as &$item){
            $tmpData = $mapper[$item[1]];
            //$item[0] = $item[0]." | ". $tmpData["category_suggestion"];
            $item[3] = $tmpData["service_identifier"];
        }

    }

    static public function slideField($from, $field, $uid,$retrunFull = false,$rootLevel = false) {
        $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($from);
        $queryBuilder->getRestrictions()->removeByType(\TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction::class);

        $result = $queryBuilder
            ->select('uid', 'pid','is_siteroot', $field)
            ->from($from)
            ->where(
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0,\Doctrine\DBAL\ParameterType::INTEGER)),
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid,))
            )
            ->executeQuery();

        $fetch = $result->fetchAssociative();
        if($fetch == false){
            return NULL;
        }

        if($rootLevel === true && $fetch["is_siteroot"] == 0){
            return self::slideField($from, $field, $fetch['pid'],$retrunFull,$rootLevel);
        }

        if ( (empty($fetch[$field]) || $fetch[$field] == 0) && $rootLevel == false  ) {
            return self::slideField($from, $field, $fetch['pid'],$retrunFull,$rootLevel);
        } else {
            if($retrunFull === true){
                return  $fetch;
            }
            return $fetch[$field];
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
                ["","All"],
                ["unknown","Unknown"],
            ];

            while ($row = $result->fetchAssociative()) {
                $filter[$row["identifier"]] = [$row["identifier"], $row["title"]];
            }

            return $filter;
        }catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex){
            return false;
        }
    }

    public static function getCookiesMultiSelectFilterItems(){
        try{
            $db = self::getDatabase();
            $result = $db->createQueryBuilder()->select("uid","service_identifier","name")->from('tx_cfcookiemanager_domain_model_cookie')->executeQuery();
            $filter = [
                ["","All"],
                ["unknown","Unknown"],
            ];

            while ($row = $result->fetchAssociative()) {
                $filter[$row["service_identifier"]] = [$row["service_identifier"], $row["service_identifier"]];
            }
          //  DebuggerUtility::var_dump($filter);
          //  die();
            return $filter;
        }catch (\Doctrine\DBAL\Exception\TableNotFoundException $ex){
            return false;
        }
    }




}
