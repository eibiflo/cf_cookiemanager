<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Model;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2023 Florian Eibisberger, CodingFreaks
 */

/**
 * External API Scans
 */
class Scans extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * domain
     *
     * @var string
     */
    protected $domain = '';

    /**
     * clickConsent
     *
     * @var bool
     */
    protected $clickConsent = false;

    /**
     * consentXpath
     *
     * @var string
     */
    protected $consentXpath = '';

    /**
     * provider
     *
     * @var string
     */
    protected $provider = '';

    /**
     * unknownprovider
     *
     * @var array
     */
    protected $unknownprovider = [];

    /**
     * foundProvider
     * has no Database field, because it's only used in API response (getScansForStorageAndLanguage)
     * Deprecated: Creation of dynamic property since php8.2
     * @var string
     */
    protected $foundProvider = 0;

    /**
     * cookies
     *
     * @var string
     */
    protected $cookies = '';

    /**
     * scannedSites
     *
     * @var string
     */
    protected $scannedSites = '';

    /**
     * maxSites
     *
     * @var string
     */
    protected $maxSites = '';

    /**
     * identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * status
     *
     * @var string
     */
    protected $status = '';

    /**
     * Returns the domain
     *
     * @return string
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Sets the domain
     *
     * @param string $domain
     * @return void
     */
    public function setDomain(string $domain)
    {
        $this->domain = $domain;
    }

    /**
     * Returns the clickConsent
     *
     * @return bool
     */
    public function getClickConsent()
    {
        return $this->clickConsent;
    }

    /**
     * Sets the clickConsent
     *
     * @param bool $clickConsent
     * @return void
     */
    public function setClickConsent(bool $clickConsent)
    {
        $this->clickConsent = $clickConsent;
    }

    /**
     * Returns the boolean state of clickConsent
     *
     * @return bool
     */
    public function isClickConsent()
    {
        return $this->clickConsent;
    }

    /**
     * Returns the consentXpath
     *
     * @return string
     */
    public function getConsentXpath()
    {
        return $this->consentXpath;
    }

    /**
     * Sets the consentXpath
     *
     * @param string $consentXpath
     * @return void
     */
    public function setConsentXpath(string $consentXpath)
    {
        $this->consentXpath = $consentXpath;
    }

    /**
     * Returns the provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Sets the provider
     *
     * @param string $provider
     * @return void
     */
    public function setProvider(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns the unknownprovider
     *
     * @return string
     */
    public function getUnknownprovider()
    {
        return $this->unknownprovider;
    }

    /**
     * Sets the unknownprovider
     *
     * @param array $unknownprovider
     * @return void
     */
    public function setUnknownprovider(array $unknownprovider)
    {
        $this->unknownprovider = $unknownprovider;
    }

    /**
     * @return int
     */
    public function getFoundProvider(): int
    {
        return $this->foundProvider;
    }

    /**
     * @param int $foundProvider
     */
    public function setFoundProvider(int $foundProvider): void
    {
        $this->foundProvider = $foundProvider;
    }

    /**
     * Returns the cookies
     *
     * @return string
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Sets the cookies
     *
     * @param string $cookies
     * @return void
     */
    public function setCookies(string $cookies)
    {
        $this->cookies = $cookies;
    }

    /**
     * Returns the scannedSites
     *
     * @return string
     */
    public function getScannedSites()
    {
        return $this->scannedSites;
    }

    /**
     * Sets the scannedSites
     *
     * @param string $scannedSites
     * @return void
     */
    public function setScannedSites(string $scannedSites)
    {
        $this->scannedSites = $scannedSites;
    }

    /**
     * Returns the maxSites
     *
     * @return string
     */
    public function getMaxSites()
    {
        return $this->maxSites;
    }

    /**
     * Sets the maxSites
     *
     * @param string $maxSites
     * @return void
     */
    public function setMaxSites(string $maxSites)
    {
        $this->maxSites = $maxSites;
    }

    /**
     * Returns the identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the identifier
     *
     * @param string $identifier
     * @return void
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns the status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Sets the status
     *
     * @param string $status
     * @return void
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }



}
