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
     * This test checks if the module is visible in the backend, and the welcome screen is shown if no data is in the database when a root page is selected
     * @test
     */
    public function welcomeScreenIsVisibleNoDataInDatabase(BackendTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes-list .node');
        // click on Root Page (UID=1)
        $I->clickWithLeftButton('.node[data-id="1"]');
        $I->switchToContentFrame();
        $I->wait(2); //Wait for the page to load
        //Can See the Cookiemanager Welcome Screen with Start Configuration button
        $I->see('Welcome to CodingFreaks Cookie Manager');
        $I->see('Start Configuration');
    }

    /**
     * This Test runs the onboarding wizard to import cookie data from the API.
     * It walks through all 3 steps: consent, API key (skip), and install.
     * @test
     */
    public function startConfigurationWizardImportsData(BackendTester $I): void
    {
        // Select the root page
        $I->switchToMainFrame();
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes-list .node');
        // click on Root Page (UID=1)
        $I->clickWithLeftButton('.node[data-id="1"]');
        $I->switchToContentFrame();
        $I->wait(2);

        // Click Start Configuration to open the onboarding wizard
        $I->click('.startConfiguration');
        $I->waitForElementVisible('#cf-onboarding-container');

        // Step 1: Select opt-out for telemetry consent (click label, not hidden radio input)
        $I->click('label[for="consentOptOut"]');
        $I->click('.cf-next-btn');
        $I->wait(1);

        // Step 2: Skip API key configuration (click Next with empty fields)
        $I->executeJS('document.querySelector(".cf-next-btn").click()');
        $I->wait(1);

        // Step 3: Click Install Presets to trigger data import from API
        $I->waitForElementVisible('.cf-install-btn');
        $I->executeJS('document.querySelector(".cf-install-btn").click()');
        $I->wait(15); //Wait for the API call and data import to complete

        // Dismiss the success modal (rendered in the main frame by TYPO3's Modal API)
        $I->switchToMainFrame();
        $I->waitForElement('.t3js-modal.modal-severity-success', 20);
        $I->click('.t3js-modal.modal-severity-success .btn');
        $I->waitForElementNotVisible('.t3js-modal', 10);
        $I->wait(2); //Wait for content frame reload after modal close

        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes-list .node');
        // click on Root Page (UID=1)
        $I->clickWithLeftButton('.node[data-id="1"]');
        $I->switchToContentFrame();
        $I->wait(3);
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
        $I->waitForElement('#typo3-pagetree-tree .nodes-list .node');
        // click on Root Page (UID=1)
        $I->clickWithLeftButton('.node[data-id="1"]');
        $I->switchToContentFrame();
        $I->wait(2); //Wait for the page to load

        //Click on the Start Configuration Button from the External Media Category
        $I->click('div[data-module-name="cookiesettings"] [data-category="externalmedia"] .setting-button');

        //Scroll to CookieServices
        $I->scrollTo("#EditDocumentController  fieldset:nth-child(5) > div");

        //select YouTube form MultiSelect
        $I->selectOption('[data-relatedfieldname="data[tx_cfcookiemanager_domain_model_cookiecartegories][3][cookie_services]"]', 'YouTube');

        //Save the Form
        $I->click('button[name="_savedok"]');
        //Close Form and go back to the Module Main Page
        $I->click('.t3js-editform-close');


        // Select the root page
        $I->switchToMainFrame();
        $I->wait(1);
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->waitForElement('#typo3-pagetree-tree .nodes-list .node');
        // click on Root Page (UID=1)
        $I->clickWithLeftButton('.node[data-id="1"]');
        $I->switchToContentFrame();
        //Can See the Cookiemanager Backend Module
        $I->see('YouTube', '[data-category="externalmedia"]');

    }

}
