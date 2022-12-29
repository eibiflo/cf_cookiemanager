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
class CookieCartegoriesControllerTest extends UnitTestCase
{
    /**
     * @var \CodingFreaks\CfCookiemanager\Controller\CookieCartegoriesController|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->getMockBuilder($this->buildAccessibleProxy(\CodingFreaks\CfCookiemanager\Controller\CookieCartegoriesController::class))
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
    public function listActionFetchesAllCookieCartegoriesFromRepositoryAndAssignsThemToView(): void
    {
        $allCookieCartegories = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\ObjectStorage::class)
            ->disableOriginalConstructor()
            ->getMock();

        $cookieCartegoriesRepository = $this->getMockBuilder(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository::class)
            ->onlyMethods(['findAll'])
            ->disableOriginalConstructor()
            ->getMock();
        $cookieCartegoriesRepository->expects(self::once())->method('findAll')->will(self::returnValue($allCookieCartegories));
        $this->subject->_set('cookieCartegoriesRepository', $cookieCartegoriesRepository);

        $view = $this->getMockBuilder(ViewInterface::class)->getMock();
        $view->expects(self::once())->method('assign')->with('cookieCartegories', $allCookieCartegories);
        $this->subject->_set('view', $view);

        $this->subject->listAction();
    }
}
