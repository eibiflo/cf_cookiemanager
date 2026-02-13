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
        $I->amOnUrl('http://web');
        $I->see('Consent Required');
    }

    /**
     *
     * This test checks if the iframemanager blocks the embeded YouTube iframe
     * @test
     */
    public function isIframeManagerOverridingSeeLoadNotice(BackendTester $I): void
    {
        $I->waitForElement('.c-l-b', 10);
        $I->see('Load YouTube videos', '.c-l-b');
    }

    /**
     *
     * This test checks if the module is visible in the backend, and the warning is shown if no data is in the database if a root page is selected
     * @test
     */
    public function isConsentModuleLaunching(BackendTester $I): void
    {
        $I->see('Consent Required'); //See Consent Required
        $I->click("#c-p-bn"); //Click Accept all / Consent primary Btn
        $I->wait(4);
        $I->switchToIFrame('[src="https://www.youtube-nocookie.com/embed/RCJdPiogUIk"]'); //Switch to YT Video
        $I->see("Don't make random HTTP requests"); //Check if YT Video is loaded
        $I->switchToMainFrame();
        $I->click('[data-cc="c-settings"]'); //Click on Settings
        $I->see("Cookie Categories"); //Check if Cookie Settings Modal is visible
    }

}