<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller\BackendAjax;


use CodingFreaks\CfCookiemanager\Service\ThumbnailService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

final class ThumbnailController
{

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private ThumbnailService                  $thumbnailService

    )
    {
    }

    /**
     * Clears the thumbnail cache.
     * @return ResponseInterface
     */
    public function clearThumbnailCache(ServerRequestInterface $request): ResponseInterface
    {
        $success = $this->thumbnailService->clearThumbnailCache();

        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(
            [
                'clearSuccess' => $success,
            ],
            JSON_THROW_ON_ERROR
        ));
        return $response;
    }

    /**
     * Retrieves the current backend user.
     *
     * This method returns the current backend user authentication object.
     * It is used to get information about the logged-in backend user.
     *
     * @return BackendUserAuthentication The backend user authentication object.
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}