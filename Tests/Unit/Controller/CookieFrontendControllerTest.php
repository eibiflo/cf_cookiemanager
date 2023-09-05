<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * TODO Test case
 *
 * @author Florian Eibisberger 
 */
class CookieFrontendControllerTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Controller\CookieFrontendController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\CodingFreaks\CfCookiemanager\Controller\CookieFrontendController::class))
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function listActionFetchesAllCookieFrontendsFromRepositoryAndAssignsThemToView(): void
    {

    }
}
