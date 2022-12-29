<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022
 */

/**
 * CookieCartegoriesController
 */
class CookieCartegoriesController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * cookieCartegoriesRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository
     */
    protected $cookieCartegoriesRepository = null;

    /**
     * cookieServiceRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository
     */
    protected $cookieServiceRepository = null;

    /**
     * cookieFrontendRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository
     */
    protected $cookieFrontendRepository = null;

    /**
     * cookieRepository
     *
     * @var \CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository
     */
    protected $cookieRepository = null;

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository
     */
    public function injectCookieCartegoriesRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository $cookieCartegoriesRepository)
    {
        $this->cookieCartegoriesRepository = $cookieCartegoriesRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository
     */
    public function injectCookieFrontendRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieFrontendRepository $cookieFrontendRepository)
    {
        $this->cookieFrontendRepository = $cookieFrontendRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository
     */
    public function injectCookieServiceRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository $cookieServiceRepository)
    {
        $this->cookieServiceRepository = $cookieServiceRepository;
    }

    /**
     * @param \CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository $cookieRepository
     */
    public function injectCookieRepository(\CodingFreaks\CfCookiemanager\Domain\Repository\CookieRepository $cookieRepository)
    {
        $this->cookieRepository = $cookieRepository;
    }

    /**
     * action list
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function listAction(): \Psr\Http\Message\ResponseInterface
    {
        $tabs = [
            "settings" => [
                "title" => "Cookie Frontend Settings",
                "identifier" => "settings"
            ],
            "categories" => [
                "title" => "Cookie Categories",
                "identifier" => "categories"
            ],
            "services" => [
                "title" => "Cookie Services",
                "identifier" => "services"
            ]
        ];

        if (empty($this->cookieServiceRepository->getAllServices($this->request))) {
            //Looks like fresh install, no data
            //TODO Language API
          // $this->cookieCartegoriesRepository->insertFromAPI();
          //  $this->cookieServiceRepository->insertFromAPI();
          //  $this->cookieRepository->insertFromAPI();
        }




        $this->view->assignMultiple(
            [
                'cookieCartegories' => $this->cookieCartegoriesRepository->getAllCategories($this->request),
                'cookieServices' => $this->cookieServiceRepository->getAllServices($this->request),
                'cookieFrontends' => $this->cookieFrontendRepository->getAllFrontends($this->request),
                'tabs' => $tabs
            ]
        );
        return $this->htmlResponse();
    }
}
