<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Tests\Unit\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Test case
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
            ->onlyMethods(['redirect', 'forward', 'addFlashMessage'])
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
        $allCookieFrontends = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cookieFrontendRepository = $this->getMockBuilder(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $cookieFrontendRepository->expects(self::once())->method('findAll')->will(self::returnValue($allCookieFrontends));
        $this->subject->_set('cookieFrontendRepository', $cookieFrontendRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('cookieFrontends', $allCookieFrontends);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }
}
