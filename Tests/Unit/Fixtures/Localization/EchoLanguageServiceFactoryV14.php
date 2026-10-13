<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Fixtures\Localization;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * LanguageServiceFactory for unit tests on TYPO3 14, returns the LanguageService it was given.
 *
 * TYPO3 14 declares LanguageServiceFactory readonly, and a child of a readonly class must be readonly too.
 * Pick the class for the installed core with EchoLanguageServiceFactory::create().
 */
readonly class EchoLanguageServiceFactoryV14 extends LanguageServiceFactory implements SingletonInterface
{
    public function __construct(private LanguageService $languageService)
    {
    }

    public function create(Locale|string $locale): LanguageService
    {
        return $this->languageService;
    }

    public function createFromUserPreferences(?AbstractUserAuthentication $user): LanguageService
    {
        return $this->languageService;
    }
}
