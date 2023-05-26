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

    //TODO Add Language Overlay Required....
    public function addCookieManagerToRequired($lang){
        $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();

        foreach ($lang as $lang_config){
            if(empty($lang_config)){
                die("Invalid Typo3 Site Configuration");
            }
            foreach ($lang_config as $lang) {
                $cfcookiemanager = $this->cookieServiceRepository->getServiceByIdentifier("cfcookiemanager",$lang["language"]["languageId"],[$lang["rootSite"]]);
                if(!empty($cfcookiemanager[0])){
                    $category = $this->cookieCategoriesRepository->getCategoryByIdentifier($cfcookiemanager[0]->getCategorySuggestion(),$lang["language"]["languageId"],[$lang["rootSite"]])[0];
                    //Check if exists
                    $allreadyExists = false;
                    foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
                        if ($currentlySelected->getIdentifier() == $cfcookiemanager[0]->getIdentifier()) {
                            $allreadyExists = true;
                        }
                    }
                    if (!$allreadyExists) {
                        $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm  (uid_local,uid_foreign,sorting,sorting_foreign) VALUES (" . $category->getUid() . "," .  $cfcookiemanager[0]->getUid() . ",0,0)";
                        $results = $con->executeQuery($sqlStr);
                    }
                }
            }
        }
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
        $languagesUsed = [];
        $domains = [];
        $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites(0);
        foreach ($sites as $siteConfig => $rootsite) {
            foreach ($rootsite->getAllLanguages() as $language) {
                $languagesUsed[$siteConfig][$language->getTwoLetterIsoCode()] = [
                    "language" =>$language->toArray(),
                    "rootSite" =>$rootsite->getRootPageId()
                ];
            }
        }


        $this->cookieFrontendRepository->insertFromAPI($languagesUsed);
        $this->cookieCategoriesRepository->insertFromAPI($languagesUsed);
        $this->cookieServiceRepository->insertFromAPI($languagesUsed);
        $this->cookieRepository->insertFromAPI($languagesUsed);
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
        return true;
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