<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

class SiteService
{
    private int $defaultLanguageId;

    public function __construct(
        private PageRepository $pageRepository,
        private SiteFinder $siteFinder,
    ) {

    }

    /**
     *
     * Retrieves the preview languages for a given page ID.
     *
     * @param int $pageId The ID of the storage page for which to fetch the preview languages.
     * @return array An associative array of language IDs and their corresponding titles.
     * @throws SiteNotFoundException If the site associated with the page ID cannot be found.
     */
    public function getPreviewLanguages(int $pageId,$backendUser): array
    {
        $languages = [];
        $modSharedTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['SHARED.'] ?? [];
        if (($modSharedTSconfig['view.']['disableLanguageSelector'] ?? false) === '1') {
            return $languages;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
            $siteLanguages = $site->getAvailableLanguages($backendUser, false, $pageId);

            foreach ($siteLanguages as $siteLanguage) {
                $languageAspectToTest = LanguageAspectFactory::createFromSiteLanguage($siteLanguage);
                // @extensionScannerIgnoreLine
                $siteLangUID = $siteLanguage->getLanguageId(); // Ignore Line of false positive
                $page = $this->pageRepository->getPageOverlay($this->pageRepository->getPage($pageId), $siteLangUID);
                if ($this->pageRepository->isPageSuitableForLanguage($page, $languageAspectToTest)) {
                    $languages[$siteLangUID] = [
                        'title' => $siteLanguage->getTitle(),
                        'locale-short' =>  $this->fallbackToLocales($siteLanguage->getLocale()->getName())
                    ];
                }
            }
        } catch (SiteNotFoundException $e) {
            // do nothing
        }
        return $languages;
    }

    /**
     * Determines the locale based on the provided locale string.
     *
     * This method checks if the provided locale string contains any of the allowed unknown locales.
     * If a match is found, it returns the first matching locale. If no match is found, it defaults to "en".
     *
     * @param string $locale The locale string to check.
     * @return string The matched locale or "en" if no match is found.
     */
    public function fallbackToLocales($locale): string
    {
        //fallback to english, if no API KEY is used on a later state.
        $allowedUnknownLocales = [
            "de",
            "en",
            "es",
            "it",
            "fr",
            "nl",
            "pl",
            "pt",
            "da",
        ];
        foreach ($allowedUnknownLocales as $allowedUnknownLocale) {
            if (strpos(strtolower($locale), $allowedUnknownLocale) !== false) {
                //return the first match
                return $allowedUnknownLocale;
            }
        }
        return "en"; //fallback to english
    }



}