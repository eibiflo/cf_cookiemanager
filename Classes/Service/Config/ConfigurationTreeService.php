<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Service\Config;

use CodingFreaks\CfCookiemanager\Domain\Model\CookieCartegories;
use CodingFreaks\CfCookiemanager\Domain\Model\CookieService;
use CodingFreaks\CfCookiemanager\Domain\Repository\CookieCartegoriesRepository;

/**
 * Service for building the backend configuration tree.
 *
 * Builds a structured tree of categories and services
 * for display in the backend module.
 */
final class ConfigurationTreeService
{
    public function __construct(
        private readonly CookieCartegoriesRepository $cookieCartegoriesRepository,
    ) {}

    /**
     * Build the configuration tree for the given storage and language.
     *
     * @param array $storageUids The storage page UIDs
     * @param int|false $languageId The language ID or false for default
     * @return array The configuration tree structure
     */
    public function build(array $storageUids, int|false $languageId = false): array
    {
        $configurationTree = [];
        $allCategories = $this->cookieCartegoriesRepository->getAllCategories($storageUids, $languageId);

        foreach ($allCategories as $category) {
            $configurationTree[$category->getUid()] = $this->buildCategoryEntry($category);
        }

        return $configurationTree;
    }

    /**
     * Build a single category entry for the tree.
     *
     * @param CookieCartegories $category The category model
     * @return array The category entry structure
     */
    private function buildCategoryEntry(CookieCartegories $category): array
    {
        $services = $category->getCookieServices();
        $servicesData = $this->buildServicesData($services);

        return [
            'uid' => $category->getUid(),
            'localizedUid' => $category->_getProperty('_localizedUid'),
            'category' => $category,
            'countServices' => count($services),
            'services' => $servicesData,
        ];
    }

    /**
     * Build services data array from service collection.
     *
     * @param iterable $services The services collection
     * @return array The services data array
     */
    private function buildServicesData(iterable $services): array
    {
        $servicesData = [];

        foreach ($services as $service) {
            $servicesData[] = $this->buildServiceEntry($service);
        }

        return $servicesData;
    }

    /**
     * Build a single service entry.
     *
     * @param CookieService $service The service model
     * @return array The service entry structure
     */
    private function buildServiceEntry(CookieService $service): array
    {
        $variables = $service->getUnknownVariables();

        // Normalize variables - getUnknownVariables returns true if no variables
        if ($variables === true) {
            $variables = [];
        }

        $serviceData = $service->_getProperties();
        $serviceData['localizedUid'] = $service->_getProperty('_localizedUid');
        $serviceData['variablesUnknown'] = array_unique($variables);

        return $serviceData;
    }
}
