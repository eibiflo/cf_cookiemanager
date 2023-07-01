<?php
// Build/Scripts/runTests.sh -s functional -p 8.1
// Build/Scripts/runTests.sh -s composerInstall
declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Functional;

use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 *
 * @author Florian Eibisberger 
 */
class BasicTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/cf_cookiemanager',
    ];

    /**
     * Just a dummy to show that at least one test is actually executed
     *
     * @test
     */
    public function dummy(): void
    {
        $this->assertTrue(true);
    }
}
