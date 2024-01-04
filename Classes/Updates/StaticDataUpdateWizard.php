<?php


namespace CodingFreaks\CfCookiemanager\Updates;

use Cassandra\Exception\ExecutionException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;


class StaticDataUpdateWizard implements UpgradeWizardInterface
{

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieServiceRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository
     */
    protected $cookieCategoriesRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository
     */
    protected $cookieFrontendRepository;

    /**
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository
     */
    protected $cookieRepository;


    public function __construct(
        CookieServiceRepository     $cookieServiceRepository,
        CookieCartegoriesRepository $cookieCategoriesRepository,
        CookieFrontendRepository    $cookieFrontendRepository,
        CookieRepository    $cookieRepository
    )
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
        $this->cookieCategoriesRepository = $cookieCategoriesRepository;
        $this->cookieFrontendRepository = $cookieFrontendRepository;
        $this->cookieRepository = $cookieRepository;
    }

    /**
     * Return the identifier for this wizard
     * This should be the same string as used in the ext_localconf class registration
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'CfCookiemanager_staticdataUpdateWizard';
    }

    /**
     * Return the speaking name of this wizard
     *
     * @return string
     */
    public function getTitle(): string
    {
        return 'Cookiemanager Static Data Update';
    }

    /**
     * Return the description for this wizard
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Inserts Frontend Strings,Services and Categories from Cookie API';
    }


    public function addCookieManagerToRequired($lang){
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();

        foreach ($lang as $lang_config){
            if(empty($lang_config)){
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {
                $cfcookiemanager = $this->cookieServiceRepository->getServiceByIdentifier("cfcookiemanager",$lang["language"]["languageId"],[$lang["rootSite"]]);
                if(!empty($cfcookiemanager[0])){
                    $category = $this->cookieCategoriesRepository->getCategoryByIdentifier($cfcookiemanager[0]->getCategorySuggestion(), $lang["language"]["languageId"], [$lang["rootSite"]])[0];
                    //Check if exists
                    $allreadyExists = false;
                    //print_r($category->getCookieServices()->toArray());
                    foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
                        if ($currentlySelected->getIdentifier() == $cfcookiemanager[0]->getIdentifier()) {
                            $allreadyExists = true;
                        }
                    }
                    if (!$allreadyExists) {
                        $cuid =  $category->getUid();
                        $suid = $cfcookiemanager[0]->getUid();
                        if ($lang["language"]["languageId"] !== 0) {
                            $cuid = $category->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                        }
                        if ($lang["language"]["languageId"] !== 0) {
                            $suid = $cfcookiemanager[0]->_getProperty("_localizedUid"); // Since 12. AbstractDomainObject::PROPERTY_LOCALIZED_UID
                        }

                        $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $cuid . "," .  $suid . ",0,0)";
                        $results = $con->executeQuery($sqlStr);
                    }
                }
            }
        }
    }


    public function fallbackToLocales($locale): string {

        $allowedUnknownLocales = [
            "de" ,
            "en"
        ];
        foreach ($allowedUnknownLocales as $allowedUnknownLocale) {
            if(strpos(strtolower($locale),$allowedUnknownLocale) !== false){
                //return the first match
                return $allowedUnknownLocale;
            }
        }
        return "en"; //fallback to english
    }


    /**
     * Execute the update
     *
     * Insert Static Data and Services from API to Extension Database
     *
     * @return bool
     */
    public function executeUpdate(): bool
    {

        //in executeUpdate, lets check if our Data folder is present and the 4 json files are valid. If so, do not execute InsertFromApi lets make a new function insertFromLocaleData
        $languagesUsed = [];
        $domains = [];
        $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites(0);
        foreach ($sites as $siteConfig => $rootsite) {
            //typo 12 and 11 differ in language code
            $versionInformation = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
            $languagesUsed[$siteConfig] = [];
            foreach ($rootsite->getAllLanguages() as $language) {
                $identifier = $versionInformation->getMajorVersion() >= 12 ? $language->getLocale()->getName() : $language->getLocale(); //Locale name like de_DE.UTF-8

                $languagesUsed[$siteConfig][$identifier] = [
                    "language" => $language->toArray(),
                    "langCode" => $this->fallbackToLocales($identifier),
                    "rootSite" => $rootsite->getRootPageId()
                ];
            }
        }

        $repositories = [
            "frontends" => $this->cookieFrontendRepository,
            "categories" => $this->cookieCategoriesRepository,
            "services" => $this->cookieServiceRepository,
            "cookie" => $this->cookieRepository
        ];


        // Define the names of the locale JSON files for offline configuration
        $jsonFiles = ['frontends.json', 'categories.json', 'services.json', 'cookies.json'];
        // Define the path to the locale preset Data folder
        $dataFolderPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cf_cookiemanager') . 'Resources/Static/Data/';


        $localInstall = false;
        foreach ($repositories as $identifier => $repository) {
            // Check if the Data folder exists
            if (is_dir($dataFolderPath)) {
                // Check if all JSON files exist
                foreach (["en","de"] as $language) {
                    if (file_exists($dataFolderPath . $identifier. '/' . $language . '.json')) {
                        // If a JSON file does not exist, call the insertFromAPI() function and return
                        $localInstall = true;
                    }
                }
            }

            //If not local install, insert from API
            if(!$localInstall){
                if (!$repository->insertFromAPI($languagesUsed)) {
                    return false;
                }
            }else{
                //If local install, insert from locale data
                if (!$repository->insertFromAPI($languagesUsed,true)) {
                    return false;
                }
            }
        }

        $this->addCookieManagerToRequired($languagesUsed);
        return true;
    }

    /**
     * Is an update necessary?
     *
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        if (count($this->cookieServiceRepository->findAll()) === 0) {
            return true;
        }
        return false;
    }

    /**
     * Returns an array of class names of prerequisite classes
     *
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
            ReferenceIndexUpdatedPrerequisite::class,
        ];
    }
}