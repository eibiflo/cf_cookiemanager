<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Functional\Service;

use CodingFreaks\CfCookiemanager\Service\AutoconfigurationService;
use CodingFreaks\CfCookiemanager\Utility\HelperUtility;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The scan import writes the default language. These tests cover that it also reaches the
 * translations, as saving a category in the backend does through allowLanguageSynchronization.
 */
final class AutoconfigurationTranslationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'codingfreaks/cf-cookiemanager',
    ];

    private Connection $con;

    protected function setUp(): void
    {
        parent::setUp();
        $this->con = HelperUtility::getDatabase();
        // The fixtures leave out the many text columns, which have no default in strict mode
        $this->con->executeStatement("SET SESSION sql_mode = ''");

        // Category "externalmedia" and service "youtube", each with a German translation
        $this->con->insert('tx_cfcookiemanager_domain_model_cookiecartegories', ['uid' => 3, 'pid' => 1, 'sys_language_uid' => 0, 'identifier' => 'externalmedia']);
        $this->con->insert('tx_cfcookiemanager_domain_model_cookiecartegories', ['uid' => 10, 'pid' => 1, 'sys_language_uid' => 1, 'l10n_parent' => 3, 'identifier' => 'externalmedia']);
        $this->con->insert('tx_cfcookiemanager_domain_model_cookieservice', ['uid' => 42, 'pid' => 1, 'sys_language_uid' => 0, 'identifier' => 'youtube']);
        $this->con->insert('tx_cfcookiemanager_domain_model_cookieservice', ['uid' => 102, 'pid' => 1, 'sys_language_uid' => 1, 'l10n_parent' => 42, 'identifier' => 'youtube']);
    }

    #[Test]
    public function aServiceLinkedToACategoryIsLinkedInEveryTranslation(): void
    {
        $this->invoke('linkTranslatedServiceToCategory', 3, 42);
        $this->invoke('linkTranslatedServiceToCategory', 3, 42);

        $rows = $this->con->select(['uid_local', 'uid_foreign'], 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm')->fetchAllAssociative();
        self::assertSame([['uid_local' => 10, 'uid_foreign' => 102]], $this->ints($rows));
    }

    #[Test]
    public function aServiceWithoutTranslationIsSkipped(): void
    {
        $this->con->delete('tx_cfcookiemanager_domain_model_cookieservice', ['uid' => 102]);

        $this->invoke('linkTranslatedServiceToCategory', 3, 42);

        self::assertSame(0, (int)$this->con->count('*', 'tx_cfcookiemanager_cookiecartegories_cookieservice_mm', []));
    }

    #[Test]
    public function aCookieFoundByTheScanGetsATranslationLinkedToTheTranslatedService(): void
    {
        $this->con->insert('tx_cfcookiemanager_domain_model_cookie', [
            'uid' => 500, 'pid' => 1, 'sys_language_uid' => 0, 'name' => 'YSC', 'domain' => '.youtube.com',
            'path' => '/', 'expiry' => 0, 'service_identifier' => 'youtube',
        ]);

        $this->invoke('linkTranslatedCookieToService', 42, 500);
        $this->invoke('linkTranslatedCookieToService', 42, 500);

        $translations = $this->con->select(['uid', 'sys_language_uid', 'l10n_parent', 'name', 'domain'], 'tx_cfcookiemanager_domain_model_cookie', ['l10n_parent' => 500])->fetchAllAssociative();
        self::assertCount(1, $translations);
        self::assertSame(1, (int)$translations[0]['sys_language_uid']);
        self::assertSame('YSC', $translations[0]['name']);
        self::assertSame('.youtube.com', $translations[0]['domain']);

        $links = $this->con->select(['uid_local', 'uid_foreign'], 'tx_cfcookiemanager_cookieservice_cookie_mm')->fetchAllAssociative();
        self::assertSame([['uid_local' => 102, 'uid_foreign' => (int)$translations[0]['uid']]], $this->ints($links));
    }

    private function invoke(string $method, int ...$arguments): void
    {
        $service = $this->get(AutoconfigurationService::class);
        (new \ReflectionMethod($service, $method))->invoke($service, $this->con, ...$arguments);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, int>>
     */
    private function ints(array $rows): array
    {
        return array_map(static fn (array $row): array => array_map('intval', $row), $rows);
    }
}
