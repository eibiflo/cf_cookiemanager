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

namespace CodingFreaks\CfCookiemanager\Tests\Acceptance\Backend\Dashboard;

use CodingFreaks\CfCookiemanager\Tests\Acceptance\Support\BackendTester;


/**
 * Tests Backend Module of the CookieManager
 */
final class SettingsBackendModuleCest
{

    public function _before(BackendTester $I): void
    {
        $I->useExistingSession('admin');
    }

    /**
     * This test checks if the module is visible in the backend, and the warning is shown if no root page is Selected
     * @test
     */
    public function infoSelectRootPageIsVisibleNoDataInDatabase(BackendTester $I): void
    {
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->switchToContentFrame();
        $I->see("Select a Root-Page to view Cookie configuration", ".tx-cf-cookiemanager .alert-message strong");
    }

    /**
     * This test checks if the module is visible in the backend, and the warning is shown if no data is in the database if a root page is selected
     * @test
     */
    public function infoInsertDataIsVisibleNoDataInDatabase(BackendTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_1 text.node-name');
        $I->switchToContentFrame();
        //Can See the Cookiemanager Backend Module
        $I->see('There appears to be no data in the database.','.tx-cf-cookiemanager .cf_manager  div.media-body > p > strong');
    }

    /**
     * TODO: Test functionality of the module with Test Data, Database Fixtures needed to do so.
     * //$I->see('CodingFreaks Cookie Manager','.tx-cf-cookiemanager .cf_manager .tab-pane.active .card-title');
     * //$I->amOnUrl('http://web:8000/typo3temp/var/tests/acceptance/typo3/module/web/CfCookiemanagerCookiesettings?id=1');
     * //$I->click('[data-modulemenu-identifier="cookiesettings"]');
     * //$I->switchToContentFrame();
     * //$I->see("Here you can configure your categories and the assigned services per language.", '//*[@id="DTM-home-1"]/div/div/div/div[2]/div[1]/p');
     */

}