<?php

namespace CodingFreaks\CfCookiemanager\Service;

use CodingFreaks\CfCookiemanager\Domain\Repository\ScansRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieServiceRepository;
use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use FilesystemIterator;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
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
    public function generateCode($service,$request) : string
    {
        $this->uriBuilder->setRequest($request);
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


    /**
     * @param $size
     * @param $precision
     * @return string
     */
    public function formatBytes($size, $precision = 2) : string
    {
        $base = log($size, 1024);
        $suffixes = array('', 'K', 'M', 'G', 'T');

        return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)];
    }

    /**
     *
     * This
     *
     * @param $folderPath
     * @return string formatted human readable size
     */
    public function getThumbnailFolderSite() : string {

        $folderPath = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/';
        if(!is_dir($folderPath)){
            return "Cache folder not found!";
        }

        $totalSize = 0;

        // Create a FilesystemIterator
        $files = new FilesystemIterator($folderPath, FilesystemIterator::SKIP_DOTS);

        // Iterate through all files and add their sizes
        foreach ($files as $file) {
            $totalSize += $file->getSize();
        }

        if($totalSize == 0){
            return "0KB";
        }

        return $this->formatBytes($totalSize);
    }

    public function clearThumbnailCache() : bool{
        $folderPath = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/';
        if(!is_dir($folderPath)){
            return false;
        }

        $files = new FilesystemIterator($folderPath, FilesystemIterator::SKIP_DOTS);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
        return true;
    }


}