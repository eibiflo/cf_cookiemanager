<?php

namespace CodingFreaks\CfCookiemanager\EventListener;


use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
class AddIntroJsModule
{

    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
            $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
            $pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour.css');
  /*          $pageRenderer->addRequireJsConfiguration(
                [
                    "waitSeconds" => 10,
                    'paths' => [
                        'jqueryDatatable' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/DataTable.min'),
                        'bootstrapTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/bootstrap-tour'),
                        'initCookieBackend' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/Backend/initCookieBackend'),
                        'TourFunctions' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/TourFunctions'),
                        'TourManager' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/TourManager'),
                        'ServiceTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/ServiceTour'),
                        'FrontendTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/FrontendTour'),
                        'CategoryTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/TutorialTours/CategoryTour'),
                    ],
                    'shim' => [
                        'initCookieBackend' => [ 'deps' => ['jquery', 'jqueryDatatable']],
                        'CategoryTour' => ['deps' => ['initCookieBackend','bootstrap','bootstrapTour']],
                        'jqueryDatatable' => ['exports' => 'jqueryDatatable'],
                    ],
                ]
            );
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/TutorialTours/TourManager'); //TODO Refactor to native ECMAScript v6/v11 modules but keep in mind that we currently support TYPO3 v11
*/
    }
}