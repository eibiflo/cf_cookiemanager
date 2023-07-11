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
        $pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'bootstrapTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/bootstrap-tour-standalone.min'),
                ],

            ]
        );
        $pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour-standalone.min.css');

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/TutorialTours/CategoriesTour');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/TutorialTours/ServiceTour');
    }
}