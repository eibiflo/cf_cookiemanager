<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Fixtures\Localization;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Builds the LanguageServiceFactory fixture that fits the installed TYPO3 core.
 *
 * Only the chosen class is loaded, the other one would not compile against this core.
 */
final class EchoLanguageServiceFactory
{
    public static function create(LanguageService $languageService): LanguageServiceFactory
    {
        if ((new \ReflectionClass(LanguageServiceFactory::class))->isReadOnly()) {
            return new EchoLanguageServiceFactoryV14($languageService);
        }

        return new EchoLanguageServiceFactoryV13($languageService);
    }
}
