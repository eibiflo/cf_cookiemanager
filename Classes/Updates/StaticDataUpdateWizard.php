<?php

//\\CodingFreaks\\CfCookiemanager\\Updates\\StaticDataUpdateWizard
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
        $sites = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(SiteFinder::class)->getAllSites(0);
        foreach ($sites as $rootsite) {
            foreach ($rootsite->getAllLanguages() as $language) {
                $languagesUsed[$language->getTwoLetterIsoCode()] = $language->toArray();
            }
        }

        //try {
            $this->cookieFrontendRepository->insertFromAPI($languagesUsed);
            $this->cookieCategoriesRepository->insertFromAPI($languagesUsed);
            $this->cookieServiceRepository->insertFromAPI($languagesUsed);
            $this->cookieRepository->insertFromAPI($languagesUsed);
        //} catch (ExecutionException $exception) {
        //    return false;
       // }

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