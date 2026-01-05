<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Middleware;

use CodingFreaks\CfCookiemanager\Service\Config\ExtensionConfigurationService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Middleware to modify HTML content for GDPR compliance.
 *
 * This middleware replaces page content when the plugin is enabled to enforce
 * cookie consent. Note: HTML DOM modification may cause issues in edge cases.
 */
class ModifyHtmlContent implements MiddlewareInterface
{
    public function __construct(
        private readonly ExtensionConfigurationService $configService,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Get configuration using the ExtensionConfigurationService
        /** @var Site|null $site */
        $site = $request->getAttribute('site');
        $rootPageId = $site?->getRootPageId() ?? 0;
        $constantConfig = $this->configService->getAll($rootPageId);

        // let it generate a response
        $response = $handler->handle($request);
        if ($response instanceof NullResponse) {
            return $response;
        }

        // extract the content
        $body = $response->getBody();
        $body->rewind();
        $content = $response->getBody()->getContents();
        $rootLine = $request->getAttribute('frontend.page.information')->getRootline()[0];

        // if Plugin is Enabled, hook the Content for GDPR Compliance
        if((int)$constantConfig["disable_plugin"] !== 1){
            $cfRenderUtility = GeneralUtility::makeInstance(\CodingFreaks\CfCookiemanager\Utility\RenderUtility::class);
            $content = $cfRenderUtility->cfHook($content, $constantConfig,$rootLine);
        }

        // push new content back into the response
        $body = new Stream('php://temp', 'rw');
        $body->write($content);
        return $response->withBody($body);
    }
}