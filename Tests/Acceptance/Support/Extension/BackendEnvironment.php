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
            'typo3conf/ext/cf_cookiemanager',
        ],
        'csvDatabaseFixtures' => [
            __DIR__ . '/../../Fixtures/BackendEnvironment.csv',
        ],
        'pathsToLinkInTestInstance' =>  [
            '/typo3conf/ext/cf_cookiemanager/Tests/Acceptance/Fixtures/SystemConfiguration/Sites' => '/typo3conf/sites',
        ]
    ];


}