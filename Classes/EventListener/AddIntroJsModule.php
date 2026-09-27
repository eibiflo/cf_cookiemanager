<?php

namespace CodingFreaks\CfCookiemanager\EventListener;

use TYPO3\CMS\Backend\Controller\Event\BeforeFormEnginePageInitializedEvent;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Makes the guided tours survive the jump from the backend module into a record form.
 *
 * A tour stores its name in the sessionStorage and continues on the FormEngine page.
 * That page therefore needs the same three assets as the module itself. Without the
 * language labels every step title and content is empty, and Bootstrap silently
 * refuses to show a popover without content, so the tour looks dead after the jump.
 */
class AddIntroJsModule
{
    public function __invoke(BeforeFormEnginePageInitializedEvent $event): void
    {
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
        $pageRenderer->addInlineLanguageLabelFile('EXT:cf_cookiemanager/Resources/Private/Language/locallang_js.xlf');
        $pageRenderer->addCssFile('EXT:cf_cookiemanager/Resources/Public/Backend/Css/bootstrap-tour.css');
        $pageRenderer->loadJavaScriptModule('@codingfreaks/cf-cookiemanager/TutorialTours/TourManager.js');
    }
}
