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

    private function categoryContainsService($category, $service): bool
    {
        foreach ($category->getCookieServices()->toArray() as $currentlySelected) {
            if ($currentlySelected->getIdentifier() === $service->getIdentifier()) {
                return true;
            }
        }
        return false;
    }

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