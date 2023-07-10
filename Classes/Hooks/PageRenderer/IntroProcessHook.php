<?php
declare(strict_types = 1);

/*
 * This file is part of the package bk2k/bootstrap-package.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace CodingFreaks\CfCookiemanager\Hooks\PageRenderer;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * IntroProcessHook
 */
class IntroProcessHook
{

    /**
     * @param array $params
     * @param \TYPO3\CMS\Core\Page\PageRenderer $pageRenderer
     */
    public function execute(&$params, &$pageRenderer): void
    {

        //Add Intro JS
        if (!($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface ||
            !ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
        ) {
            $pageRenderer->addRequireJsConfiguration(
                [
                    'paths' => [
                        'bootstrapTour' => \TYPO3\CMS\Core\Utility\PathUtility::getPublicResourceWebPath('EXT:cf_cookiemanager/Resources/Public/JavaScript/thirdparty/bootstrap-tour-standalone.min'),
                    ],

                ]
            );

            $pageRenderer->loadRequireJsModule('TYPO3/CMS/CfCookiemanager/CfCookiemanagerIntro');
            return;
        }


    }

}
