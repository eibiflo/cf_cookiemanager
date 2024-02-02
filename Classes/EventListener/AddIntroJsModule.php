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
            $pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/TutorialTours/TourManager.js');
    }
}