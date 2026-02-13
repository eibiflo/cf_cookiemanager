<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Frontend;

use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;
use CodingFreaks\CfCookiemanager\Service\VariableReplacerService;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Page\AssetCollector;

/**
 * Service for managing external JavaScript files and inline scripts.
 *
 * Extracted from CookieFrontendRepository::addExternalServiceScripts()
 *
 * Handles:
 * - External script registration with consent-aware data attributes
 * - Inline JavaScript with variable replacement
 * - Integration with TYPO3's AssetCollector
 */
final class ExternalScriptService
{
    public function __construct(
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
        private readonly VariableReplacerService $variableReplacer,
        private readonly AssetCollector $assetCollector,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Collect and register all external scripts from cookie services.
     *
     * Scripts are registered with type="text/plain" and data-service attribute
     * so they are blocked until consent is given.
     *
     * @param array $storages Storage page IDs
     * @param int $langId Language ID
     * @return int Number of scripts registered
     */
    public function collectAndRegisterScripts(array $storages, int $langId): int
    {
        $scriptCount = 0;
        $categories = $this->cookieCartegoriesRepository->getAllCategories($storages, $langId);

        foreach ($categories as $category) {
            $services = $category->getCookieServices();

            if ($services->count() === 0) {
                continue;
            }

            foreach ($services as $service) {
                $scriptCount += $this->registerServiceScripts($service);
            }
        }

        $this->logger->debug('Registered external scripts', [
            'count' => $scriptCount,
            'storages' => $storages,
            'langId' => $langId,
        ]);

        return $scriptCount;
    }

    /**
     * Register all scripts for a single cookie service.
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $service
     * @return int Number of scripts registered for this service
     */
    private function registerServiceScripts($service): int
    {
        $count = 0;
        $externalScripts = $service->getExternalScripts();
        $variables = $service->getVariablePriovider();
        $serviceIdentifier = $service->getIdentifier();

        if ($externalScripts->count() === 0) {
            return 0;
        }

        // Register external script files
        foreach ($externalScripts as $externalScript) {
            $scriptUrl = $this->processVariables($externalScript->getLink(), $variables);

            $this->assetCollector->addJavaScript(
                $externalScript->getName(),
                $scriptUrl,
                [
                    'type' => 'text/plain',
                    'external' => 1,
                    'async' => $externalScript->getAsync(),
                    'data-service' => $serviceIdentifier,
                ]
            );
            $count++;
        }

        // Register inline opt-in code if present
        $optInCode = $service->getOptInCode();
        if (!empty($optInCode)) {
            $processedCode = $this->processVariables($optInCode, $variables);
            $inlineIdentifier = $this->generateUniqueIdentifier();

            $this->assetCollector->addInlineJavaScript(
                $inlineIdentifier,
                $processedCode,
                [
                    'type' => 'text/plain',
                    'external' => 1,
                    'async' => 0,
                    'defer' => 'defer',
                    'data-service' => $serviceIdentifier,
                ]
            );
            $count++;
        }

        return $count;
    }

    /**
     * Process variable replacements in content.
     *
     * @param string $content The content with variable placeholders
     * @param iterable $variables The variables to replace
     * @return string The processed content
     */
    private function processVariables(string $content, iterable $variables): string
    {
        return $this->variableReplacer->replaceFromObjects($content, $variables);
    }

    /**
     * Generate a unique identifier for inline scripts.
     *
     * @return string A 32-character unique identifier
     */
    private function generateUniqueIdentifier(): string
    {
        return substr(
            str_replace(['+', '/', '='], '', base64_encode(random_bytes(32))),
            0,
            32
        );
    }
}
