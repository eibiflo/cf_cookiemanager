<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Frontend;

use CodingFreaks\CfCookiemanager\Utility\JavaScriptObfuscator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Service for handling consent tracking functionality.
 *
 * Extracted from:
 * - CookieFrontendRepository::addTrackingJS()
 * - CookieFrontendController::trackAction() (DB insert logic)
 */
final class TrackingService
{
    private const TRACKING_JS_PATH = 'EXT:cf_cookiemanager/Resources/Public/JavaScript/Tracking.js';
    private const TRACKING_TABLE = 'tx_cfcookiemanager_domain_model_tracking';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Generate the tracking JavaScript code.
     *
     * @param string $trackingUrl The URL to send tracking data to
     * @param bool $obfuscate Whether to obfuscate the JS code
     * @return string The tracking JavaScript code
     */
    public function generateTrackingScript(string $trackingUrl, bool $obfuscate = false): string
    {
        $jsPath = GeneralUtility::getFileAbsFileName(self::TRACKING_JS_PATH);

        if (!file_exists($jsPath)) {
            $this->logger->error('Tracking JS file not found', ['path' => $jsPath]);
            return '';
        }

        $jsCode = file_get_contents($jsPath);

        if ($jsCode === false) {
            $this->logger->error('Could not read tracking JS file', ['path' => $jsPath]);
            return '';
        }

        // Replace tracking URL placeholder with base64 encoded URL
        $jsCode = str_replace('{{tracking_url}}', base64_encode($trackingUrl), $jsCode);

        // Optionally obfuscate the code
        if ($obfuscate) {
            $jsCode = $this->obfuscateCode($jsCode);
        }

        return $jsCode;
    }

    /**
     * Record a consent action to the database.
     *
     * @param ServerRequestInterface $request The current request
     * @param int $storageUid The storage page UID
     * @return bool True on success
     */
    public function recordConsent(ServerRequestInterface $request, int $storageUid): bool
    {
        $body = $request->getParsedBody();
        $routing = $request->getAttribute('routing');
        $pageId = $routing !== null && method_exists($routing, 'getPageId')
            ? (int)$routing->getPageId()
            : 0;

        $data = $this->parseConsentData($body, $request);

        try {
            $connection = $this->connectionPool->getConnectionForTable(self::TRACKING_TABLE);
            $connection->insert(
                self::TRACKING_TABLE,
                [
                    'pid' => $storageUid,
                    'consent_page' => $pageId,
                    'language_code' => $data['languageCode'],
                    'referrer' => $data['referrer'],
                    'user_agent' => $data['userAgent'],
                    'consent_type' => $data['consentType'],
                    'consent_date' => time(),
                    'navigator' => $data['navigator'],
                ]
            );

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to record consent', [
                'error' => $e->getMessage(),
                'storageUid' => $storageUid,
            ]);
            return false;
        }
    }

    /**
     * Parse consent data from request body.
     *
     * @param array|null $body The parsed request body
     * @param ServerRequestInterface $request The current request
     * @return array{languageCode: string, referrer: string, userAgent: string, consentType: string, navigator: int}
     */
    private function parseConsentData(?array $body, ServerRequestInterface $request): array
    {
        $userAgentHeader = $request->getHeader('User-Agent');

        return [
            'navigator' => (!empty($body['navigator']) && $body['navigator'] === 'true') ? 1 : 0,
            'languageCode' => $body['languageCode'] ?? '',
            'referrer' => $body['referrer'] ?? '',
            'consentType' => $body['consent_type'] ?? '',
            'userAgent' => $userAgentHeader[0] ?? '',
        ];
    }

    /**
     * Obfuscate JavaScript code.
     */
    private function obfuscateCode(string $jsCode): string
    {
        try {
            $obfuscator = GeneralUtility::makeInstance(JavaScriptObfuscator::class);
            return $obfuscator->obfuscate($jsCode, false);
        } catch (\Exception $e) {
            $this->logger->warning('JS obfuscation failed, returning original code', [
                'error' => $e->getMessage(),
            ]);
            return $jsCode;
        }
    }

    /**
     * Build the tracking callback JavaScript for the consent configuration.
     *
     * @param string $trackingUrl The tracking endpoint URL
     * @param bool $obfuscate Whether to obfuscate the code
     * @return string JavaScript code for onFirstAction callback
     */
    public function buildTrackingCallback(string $trackingUrl, bool $obfuscate = false): string
    {
        $trackingJs = $this->generateTrackingScript($trackingUrl, $obfuscate);

        if (empty($trackingJs)) {
            return '';
        }

        return $trackingJs;
    }
}
