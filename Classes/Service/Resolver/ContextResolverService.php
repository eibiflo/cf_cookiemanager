<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Resolver;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Service for resolving context information like storage PIDs and language IDs.
 *
 * Consolidates duplicated resolution logic from:
 * - CookieFrontendController (language + storage)
 * - CookieSettingsBackendController (language)
 * - HelperUtility::slideField() (storage via rootline)
 */
final class ContextResolverService
{
    public function __construct(
        private readonly SiteFinder $siteFinder,
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
        private readonly LoggerInterface $logger,
    ) {}

    // ========================================
    // Language Resolution
    // ========================================

    /**
     * Get the current language ID from the request.
     * Uses PSR-7 request attributes (modern TYPO3 pattern).
     */
    public function getCurrentLanguageId(ServerRequestInterface $request): int
    {
        $language = $request->getAttribute('language');

        if ($language instanceof SiteLanguage) {
            return $language->getLanguageId();
        }

        // Fallback: try context aspect
        try {
            return (int)$this->context->getPropertyFromAspect('language', 'id', 0);
        } catch (\Exception $e) {
            $this->logger->warning('Could not resolve language ID from context', [
                'exception' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get the default language ID for a site.
     */
    public function getDefaultLanguageId(int $siteRootPageId): int
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($siteRootPageId);
            return $site->getDefaultLanguage()->getLanguageId();
        } catch (\Exception $e) {
            $this->logger->warning('Could not resolve default language', [
                'pageId' => $siteRootPageId,
                'exception' => $e->getMessage(),
            ]);
            return 0;
        }
    }

    /**
     * Get all available language IDs for a site.
     *
     * @return int[]
     */
    public function getAvailableLanguageIds(int $siteRootPageId): array
    {
        try {
            $site = $this->siteFinder->getSiteByRootPageId($siteRootPageId);
            $languageIds = [];

            foreach ($site->getAllLanguages() as $language) {
                $languageIds[] = $language->getLanguageId();
            }

            return $languageIds;
        } catch (\Exception $e) {
            $this->logger->warning('Could not resolve available languages', [
                'pageId' => $siteRootPageId,
            ]);
            return [0];
        }
    }

    // ========================================
    // Storage / Site Resolution
    // ========================================

    /**
     * Resolve the storage UID (site root page) from the current request.
     * Uses PSR-7 request attributes (modern TYPO3 pattern).
     */
    public function resolveStorageUid(ServerRequestInterface $request): int
    {
        $site = $request->getAttribute('site');

        if ($site instanceof Site) {
            return $site->getRootPageId();
        }

        // Fallback: try to get from routing
        $routing = $request->getAttribute('routing');
        if ($routing !== null && method_exists($routing, 'getPageId')) {
            $pageId = (int)$routing->getPageId();
            return $this->resolveStorageFromRootline($pageId) ?? $pageId;
        }

        $this->logger->warning('Could not resolve storage UID from request');
        return 0;
    }

    /**
     * Resolve the storage UID by traversing the rootline to find the site root.
     * This is the refactored version of HelperUtility::slideField().
     */
    public function resolveStorageFromRootline(int $pageId): ?int
    {
        if ($pageId <= 0) {
            return null;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        $result = $queryBuilder
            ->select('uid', 'pid', 'is_siteroot')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, \Doctrine\DBAL\ParameterType::INTEGER))
            )
            ->executeQuery()
            ->fetchAssociative();

        if ($result === false) {
            return null;
        }

        // Found site root
        if ((int)$result['is_siteroot'] === 1) {
            return (int)$result['uid'];
        }

        // Reached top of tree without finding site root
        if ((int)$result['pid'] === 0) {
            return (int)$result['uid'];
        }

        // Recurse up the tree
        return $this->resolveStorageFromRootline((int)$result['pid']);
    }

    /**
     * Get the Site object for a page ID.
     */
    public function getSiteByPageId(int $pageId): ?Site
    {
        try {
            return $this->siteFinder->getSiteByPageId($pageId);
        } catch (\Exception $e) {
            $this->logger->debug('Could not resolve site for page', [
                'pageId' => $pageId,
                'exception' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get the Site object from a request.
     */
    public function getSiteFromRequest(ServerRequestInterface $request): ?Site
    {
        $site = $request->getAttribute('site');
        return $site instanceof Site ? $site : null;
    }

    /**
     * Get all configured sites.
     *
     * @return Site[]
     */
    public function getAllSites(): array
    {
        return $this->siteFinder->getAllSites();
    }

    // ========================================
    // Combined Context Resolution
    // ========================================

    /**
     * Resolve both storage UID and language ID from a request.
     * Convenience method for controllers that need both values.
     *
     * @return array{storageUid: int, languageId: int, site: ?Site}
     */
    public function resolveContext(ServerRequestInterface $request): array
    {
        return [
            'storageUid' => $this->resolveStorageUid($request),
            'languageId' => $this->getCurrentLanguageId($request),
            'site' => $this->getSiteFromRequest($request),
        ];
    }
}
