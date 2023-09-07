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
 * Tests concerning Reports Module
 */
final class SettingsBackendModuleCest
{

    public function _before(BackendTester $I): void
    {
        $I->useExistingSession('admin');
    }

    /**
     * @test
     */
    public function demo(BackendTester $I): void
    {
        $I->click('[data-modulemenu-identifier="cookiesettings"]');
        $I->switchToContentFrame();
        $I->see("Select a Root-Page to view Cookie configuration", ".tx-cf-cookiemanager .alert-message strong");
    }

    /**
     * @test
     */
    public function canSeePages(BackendTester $I): void
    {
        //$I->amOnUrl('http://web:8000/typo3temp/var/tests/acceptance/typo3/module/web/CfCookiemanagerCookiesettings?id=1');
        //$I->click('[data-modulemenu-identifier="cookiesettings"]');
        //$I->switchToContentFrame();
        //$I->see("Here you can configure your categories and the assigned services per language.", '//*[@id="DTM-home-1"]/div/div/div/div[2]/div[1]/p');
    }

}