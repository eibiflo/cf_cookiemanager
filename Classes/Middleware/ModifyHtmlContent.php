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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Class ModifyHtmlContent, which is a middleware to modify the content of an HTML response for the GDPR compliance.
 *
 * Diese Methode ersetzt den Inhalt der Seite, wenn das Plugin aktiviert ist. Aus Verschiedenen Gründen kann dies Probleme verursachen, weil der HTML-Dom neu Gespeichert und bearbeitet wird.
 * Todo, Wir durschsurchen temporär mit dem Parser das HTML das wir ersetzten möchten, speichern aber nicht das HTML zurück in den Request, sondern suchen die position im Echten-DOM (Current Request)Dadurch versuchen wir anhand der Position das HTML zu ersetzen, auf diese Weiße wird der DOM nichtmehr durch Thirdparty bearbeitet geschläußt (PHP-Dom, HMl5-Parser, macht UTF8 Probleme falsch interpretiertes HTML usw..), was zu komischen resultaten führen kann.
 *
 * @package CodingFreaks\CfCookiemanager\Middleware
 */
class ModifyHtmlContent implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        // let it generate a response
        $response = $handler->handle($request);
        if ($response instanceof NullResponse) {
            return $response;
        }

        // extract the content
        $body = $response->getBody();
        $body->rewind();
        $content = $response->getBody()->getContents();

        // if Plugin is Enabled, hook the Content for GDPR Compliance
        if((int)$extensionConfiguration["disablePlugin"] !== 1){
            $cfRenderUtility = GeneralUtility::makeInstance(      \CodingFreaks\CfCookiemanager\Utility\RenderUtility::class);
            $content = $cfRenderUtility->cfHook($content, $extensionConfiguration);
        }

        // push new content back into the response
        $body = new Stream('php://temp', 'rw');
        $body->write($content);
        return $response->withBody($body);
    }
}