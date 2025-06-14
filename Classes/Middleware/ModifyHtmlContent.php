<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class ModifyHtmlContent, which is a middleware to modify the content of an HTML response for the GDPR compliance.
 *
 * This method replaces the content of the page if the plugin is enabled. For various reasons, this can cause problems because the HTML DOM is saved and edited again.
 *
 * @package CodingFreaks\CfCookiemanager\Middleware
 */
class ModifyHtmlContent implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $fullTypoScript = $request->getAttribute('frontend.typoscript')->getFlatSettings();
        $constantConfig = [
            "disable_plugin" => intval($fullTypoScript["plugin.tx_cfcookiemanager_cookiefrontend.frontend.disable_plugin"]) ?? 0,
            "script_blocking" => intval($fullTypoScript["plugin.tx_cfcookiemanager_cookiefrontend.frontend.script_blocking"]) ?? 0,
        ];

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