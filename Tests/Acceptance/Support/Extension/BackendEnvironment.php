<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Acceptance\Support\Extension;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Codeception\Event\SuiteEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Acceptance\Extension\BackendEnvironment as T3BackendEnvironment;

/**
 * Load various core extensions and styleguide and call styleguide generator
 */
class BackendEnvironment extends T3BackendEnvironment
{
    /**
     * Load a list of core extensions and styleguide
     *
     * @var array
     */
    protected $localConfig = [
        'coreExtensionsToLoad' => [
            'core',
            'dashboard',
            'extbase',
            'fluid',
            'backend',
            'install',
            'frontend',
            'tstemplate',
            'seo',
        ],
        'testExtensionsToLoad' => [
            'cf_cookiemanager',
        ],
        'csvDatabaseFixtures' => [
            __DIR__ . '/../../Fixtures/BackendEnvironment.csv',
        ],
        'pathsToLinkInTestInstance' =>  [
            '/typo3conf/ext/cf_cookiemanager/Tests/Acceptance/Fixtures/SystemConfiguration/Sites' => '/typo3conf/sites',
        ]
    ];

    /**
     * Create TYPO3 test environment and insert backend sessions with
     * version-correct hashed session IDs. The session hash algorithm
     * changed between TYPO3 v13 (sha256) and v14 (sha3-256), so we
     * compute hashes at runtime using DatabaseSessionBackend::hash().
     */
    public function bootstrapTypo3Environment(SuiteEvent $suiteEvent): void
    {
        parent::bootstrapTypo3Environment($suiteEvent);
        $this->createBackendSessions();
    }

    private function createBackendSessions(): void
    {
        $sessionBackend = new DatabaseSessionBackend();
        $sessionBackend->initialize('BE', ['table' => 'be_sessions', 'has_anonymous' => false]);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_sessions');

        // Plaintext session IDs must match those configured in Backend.suite.yml
        $sessions = [
            '886526ce72b86870739cc41991144ec1' => 1, // admin
            'ff83dfd81e20b34c27d3e97771a4525a' => 2, // editor
        ];

        foreach ($sessions as $plainSessionId => $userId) {
            $connection->insert('be_sessions', [
                'ses_id' => $sessionBackend->hash($plainSessionId),
                'ses_iplock' => '[DISABLED]',
                'ses_userid' => $userId,
                'ses_tstamp' => 1777777777,
                'ses_data' => '',
            ]);
        }
    }
}
