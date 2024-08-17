<?php

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Class ThumbnailService
 * @package CodingFreaks\CfCookiemanager\Service
 */
class ThumbnailService
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder
     */
    protected $uriBuilder;


    /**
     * ThumbnailService constructor.
     * @param UriBuilder $uriBuilder
     */
    public function __construct(UriBuilder $uriBuilder)
    {
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Generates a Placeholder with the Backend Thumbnail URL for the given service,
     * Placeholder is replaced with rendered width and height in the frontend
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\Service $service
     * @return string
     */
    public function generateCode($service)
    {
        $this->uriBuilder->reset();
        $this->uriBuilder->setCreateAbsoluteUri(true);
        $this->uriBuilder->setTargetPageType(1723638651);

        // Call the uriFor method to get a TrackingURL
        $thumbnailAction = $this->uriBuilder->uriFor(
            "thumbnail",
            null, // Controller arguments, if any
            "CookieFrontend",
            "cfCookiemanager",
            "IframeManagerThumbnail"
        );

        return "iframemanagerconfig.services." . $service->getIdentifier() . ".thumbnailUrl = '$thumbnailAction&cf_thumbnail=##CF-BUILDTHUMBNAIL##';";
    }
}