<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service;

use FilesystemIterator;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * Service for generating and fetching iframe thumbnails.
 *
 * Handles:
 * - Thumbnail URL generation for iframe placeholders
 * - Fetching thumbnails from external API
 * - Thumbnail cache management
 */
class ThumbnailService
{
    public function __construct(
        private readonly UriBuilder $uriBuilder,
        private readonly RequestFactory $requestFactory,
        private readonly LoggerInterface $logger,
    ) {}

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

    public function clearThumbnailCache(): bool
    {
        $folderPath = Environment::getPublicPath() . '/typo3temp/assets/cfthumbnails/';
        if (!is_dir($folderPath)) {
            return false;
        }

        $files = new FilesystemIterator($folderPath, FilesystemIterator::SKIP_DOTS);
        foreach ($files as $file) {
            unlink($file->getRealPath());
        }
        return true;
    }

    /**
     * Fetch a thumbnail from the external thumbnail service API.
     *
     * @param string $endpointUrl The thumbnail service endpoint URL
     * @param string $targetUrl The URL to generate a thumbnail for
     * @param int $width Thumbnail width in pixels
     * @param int $height Thumbnail height in pixels
     * @param string $apiKey API key identifying the TYPO3 instance
     * @param string $apiSecret API secret for authentication
     * @param string $domain The domain requesting the thumbnail
     * @return string|null The image content or null on failure
     */
    public function fetchThumbnail(
        string $endpointUrl,
        string $targetUrl,
        int $width = 1920,
        int $height = 1080,
        string $apiKey = '',
        string $apiSecret = '',
        string $domain = ''
    ): ?string {
        $formParams = [
            'url' => $targetUrl,
            'width' => $width,
            'height' => $height,
        ];

        if ($apiKey !== '') {
            $formParams['scan_api_key'] = $apiKey;
        }
        if ($apiSecret !== '') {
            $formParams['scan_api_secret'] = $apiSecret;
        }
        if ($domain !== '') {
            $formParams['domain'] = $domain;
        }

        try {
            $response = $this->requestFactory->request(
                $endpointUrl,
                'POST',
                [
                    'form_params' => $formParams,
                    'timeout' => 30,
                ]
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode === 429) {
                $this->logger->warning('Thumbnail API quota exceeded', [
                    'url' => $targetUrl,
                    'domain' => $domain,
                    'apiKey' => $apiKey,
                ]);
                return null;
            }

            if ($statusCode >= 400) {
                $this->logger->warning('Thumbnail API returned error status', [
                    'status' => $statusCode,
                    'url' => $targetUrl,
                ]);
                return null;
            }

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            $this->logger->error('Failed to fetch thumbnail', [
                'error' => $e->getMessage(),
                'url' => $targetUrl,
            ]);
            return null;
        }
    }
}