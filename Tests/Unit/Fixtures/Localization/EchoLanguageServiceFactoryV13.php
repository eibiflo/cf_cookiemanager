<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Fixtures\Localization;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * LanguageServiceFactory for unit tests on TYPO3 13, returns the LanguageService it was given.
 *
 * TYPO3 13 declares LanguageServiceFactory not readonly, and a readonly class cannot extend it.
 * Pick the class for the installed core with EchoLanguageServiceFactory::create().
 */
class EchoLanguageServiceFactoryV13 extends LanguageServiceFactory implements SingletonInterface
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
