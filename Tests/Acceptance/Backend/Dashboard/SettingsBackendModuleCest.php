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
        $I->see("Select a Root-Page to view Cookie configuration", '[data-module-name="cookiesettings"] .alert-message strong');
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
        $I->wait(2); //Wait for the page to load
        //Can See the Cookiemanager Backend Module
        $I->see('There appears to be no data in the database.');
    }

    /**
     * This Test checks if the Start Configuration Button is visible if no data is in the database, and if the button works by importing the data from the static data update wizard
     * @test
     */
    public function startConfigurationButtonWithNoDataInDatabase(BackendTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_1 text.node-name');
        $I->switchToContentFrame();
        //Can See the Cookiemanager Backend Module
        $I->click('.btn.btn-success');
        $I->wait(10); //Wait for the page to load (Importing Datasets)
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_1 text.node-name');
        $I->switchToContentFrame();
        $I->see('Here you can configure your categories and the assigned services per language.');
    }

    /**
     * Test a Category configuration with a single language, and a single service.
     * Assign YouTube to External media.
     * @test
     */
    public function AssigneYoutubeToExternalMedia(BackendTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_1 text.node-name');
        $I->switchToContentFrame();
        $I->wait(2); //Wait for the page to load

        //Click on the Start Configuration Button from the External Media Category
        $I->click('div[data-module-name="cookiesettings"] [data-category="externalmedia"] .setting-button');

        //Scroll to CookieServices
        $I->scrollTo("#EditDocumentController  fieldset:nth-child(5) > div");

        //select YouTube form MultiSelect
        $I->selectOption('[data-relatedfieldname="data[tx_cfcookiemanager_domain_model_cookiecartegories][3][cookie_services]"]', 'YouTube');

        //Save the Form
        $I->click('body > div.module > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > button');
        //Close Form and go back to the Module Main Page
        $I->click('.t3js-editform-close');


        // Select the root page
        $I->switchToMainFrame();
        $I->wait(1);
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_1 text.node-name');
        $I->switchToContentFrame();
        //Can See the Cookiemanager Backend Module
        $I->see('YouTube', '[data-category="externalmedia"]');

    }

}