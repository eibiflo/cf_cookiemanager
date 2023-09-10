<?php

declare(strict_types=1);

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

namespace CodingFreaks\CfCookiemanager\Tests\Acceptance\Backend\Frontend;

use CodingFreaks\CfCookiemanager\Tests\Acceptance\Support\BackendTester;


/**
 * Tests the Frontend of the CookieManager
 */
final class CookieFrontendCest
{

    /**
     * Setup the Frontend test
     * @param BackendTester $I
     */
    public function _before(BackendTester $I): void
    {
        $I->amOnUrl('http://web/typo3temp/var/tests/acceptance');
    }

    /**
     *
     * This test checks if the module is visible in the backend, and the warning is shown if no data is in the database if a root page is selected
     * @test
     */
    public function isConsentModuleLaunching(BackendTester $I): void
    {
        //TODO dose only Work if Data is in the Database need to add a fixture
        //$I->see('Consent Required','#c-ttl');
    }

}