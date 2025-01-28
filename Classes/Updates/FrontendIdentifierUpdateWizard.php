<?php


namespace CodingFreaks\CfCookiemanager\Updates;

use Cassandra\Exception\ExecutionException;
use ScssPhp\ScssPhp\Formatter\Debug;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\ReferenceIndexUpdatedPrerequisite;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

/*
 * This UpdateWizard is used to update the frontend datasets to the new Identifier (de) from the old Identifier (DE-de)
 */
#[UpgradeWizard('frontendIdentifierUpdateWizard')]
final class FrontendIdentifierUpdateWizard implements UpgradeWizardInterface
{

    protected CookieFrontendRepository $cookieFrontendRepository;

    public function __construct()
    {
        $this->cookieFrontendRepository = GeneralUtility::makeInstance(CookieFrontendRepository::class);
    }

    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'Coding-Freaks Frontend Dataset Migration';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'Updates old Identifier (DE-de) to new Identifier (de) in the frontend datasets to get Updates from the CookieManager API';
    }

    /**
     * Execute the update
     *
     * Called when a wizard reports that an update is necessary
     *
     * The boolean indicates whether the update was successful
     */
    public function executeUpdate(): bool
    {
        $query = $this->cookieFrontendRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(false);
        $query->getQuerySettings()->setIncludeDeleted(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $records = $query->execute();
        foreach ($records as $record) {
            $identifier = $record->getIdentifier();
            if (preg_match('/^[a-z]{2}-[A-Z]{2}$/', $identifier)) {
                $newIdentifier = strtolower(explode('-', $identifier)[0]);
                $record->setIdentifier($newIdentifier);
                $this->cookieFrontendRepository->update($record);
            }
        }
        // Persist all changes to the database
        $persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
        $persistenceManager->persistAll();
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
        $query = $this->cookieFrontendRepository->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        $query->getQuerySettings()->setIgnoreEnableFields(false);
        $query->getQuerySettings()->setIncludeDeleted(false);
        $query->getQuerySettings()->setRespectSysLanguage(false);
        $records = $query->execute();
        foreach ($records as $record) {
            $identifier = $record->getIdentifier();
            if (preg_match('/^[a-z]{2}-[A-Z]{2}$/', $identifier)) {
                return true;
            }
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
            ReferenceIndexUpdatedPrerequisite::class
        ];
    }
}