<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use CodingFreaks\CfCookiemanager\Utility\HelperUtility;

class CategoryLinkService
{
    public function __construct(
        private CookieServiceRepository $cookieServiceRepository,
        private CookieCartegoriesRepository $cookieCategoriesRepository
    ) {
    }

    /**
     * Adds the CF-CookieManager service to the required services for each language configuration.
     *
     *
     * @param array $lang The array of language configurations.
     * @param int $storage The storage UID.
     * @throws \InvalidArgumentException If the language configuration is invalid.
     */
    public function addCookieManagerToRequired(array $lang, $storage): void
    {
        $con = HelperUtility::getDatabase();

        foreach ($lang as $langKey => $langConfig) {
            if (empty($langConfig)) {
                throw new \InvalidArgumentException("Invalid Typo3 Site Configuration");
            }
            foreach ($langConfig as $lang) {
                $this->processLanguageConfig($langKey, $storage, $con);
            }
        }
    }

    /**
     * Processes the language configuration to ensure the CF-CookieManager service is added to the required services.
     *
     * This method retrieves the CF-CookieManager service and its suggested category for the given language key and storage UID.
     * It checks if the service is already included in the category and inserts it if not.
     *
     * @param int $langKey The language key.
     * @param int $storage The storage UID.
     * @param \Doctrine\DBAL\Connection $con The database connection.
     */
    private function processLanguageConfig(int $langKey, int $storage, $con): void
    {
        $cfcookiemanager = $this->cookieServiceRepository->getServiceByIdentifier("cfcookiemanager", $langKey, [$storage]);
        if (!empty($cfcookiemanager[0])) {
            $category = $this->cookieCategoriesRepository->getCategoryByIdentifier($cfcookiemanager[0]->getCategorySuggestion(), $langKey, [$storage])[0];
            if (!$this->categoryContainsService($category, $cfcookiemanager[0])) {
                $this->insertServiceIntoCategory($category, $cfcookiemanager[0], $langKey, $con);
            }
        }
    }

    /**
     * Checks if the given category already contains the specified service.
     *
     * This method iterates through the services in the category to determine if the specified service is already included.
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieCategory $category The category to check.
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $service The service to look for.
     * @return bool True if the category contains the service, false otherwise.
     */
    private function categoryContainsService($category, $service): bool
    {
        foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
            if ($currentlySelected->getIdentifier() === $service->getIdentifier()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Inserts the specified service into the given category.
     *
     * This method checks if the service is already present in the category and inserts it if not.
     * It handles the localization of the category and service UIDs based on the language ID.
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieCategory $category The category to insert the service into.
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $service The service to be inserted.
     * @param int $languageId The language ID for localization.
     * @param \Doctrine\DBAL\Connection $con The database connection.
     */
    private function insertServiceIntoCategory($category, $service, int $languageId, $con): void
    {
        $cuid = $languageId !== 0 ? $category->_getProperty("_localizedUid") : $category->getUid();
        $suid = $languageId !== 0 ? $service->_getProperty("_localizedUid") : $service->getUid();

        if (!empty($cuid) && !empty($suid)) {
            // Check if the entry already exists
            $sqlCheck = "SELECT COUNT(*) FROM tx_cfcookiemanager_cookiecartegories_cookieservice_mm WHERE uid_local = $cuid AND uid_foreign = $suid";
            $exists = $con->executeQuery($sqlCheck)->fetchOne();

            if ($exists == 0) {
                $sqlStr = "INSERT INTO tx_cfcookiemanager_cookiecartegories_cookieservice_mm (uid_local, uid_foreign, sorting, sorting_foreign) VALUES ($cuid, $suid, 0, 0)";
                $con->executeQuery($sqlStr);
            }
        } else {
            // Handle the case where $cuid or $suid is empty
            // You could throw an exception, return an error, or log the issue, depending on your application's requirements
        }
    }
}