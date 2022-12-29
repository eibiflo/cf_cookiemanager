<?php


namespace  CodingFreaks\CfCookiemanager\Utility;


use TYPO3\CMS\Core\Database\ConnectionPool;
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


    public function createStaticDatatables(){

        

    }


}
