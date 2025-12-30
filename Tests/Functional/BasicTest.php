<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Basic functional test to verify the extension loads correctly.
 */
final class BasicTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'codingfreaks/cf-cookiemanager',
    ];

    #[Test]
    public function extensionIsLoaded(): void
    {
        $this->assertTrue(
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('cf_cookiemanager'),
            'Extension cf_cookiemanager should be loaded'
        );
    }

    #[Test]
    public function tablesAreCreated(): void
    {
        // Verify that the extension's database tables exist
        $connection = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class)
            ->getConnectionForTable('tx_cfcookiemanager_domain_model_cookiecartegories');

        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        $this->assertContains(
            'tx_cfcookiemanager_domain_model_cookiecartegories',
            $tables,
            'Cookie categories table should exist'
        );

        $this->assertContains(
            'tx_cfcookiemanager_domain_model_cookieservice',
            $tables,
            'Cookie service table should exist'
        );
    }
}
