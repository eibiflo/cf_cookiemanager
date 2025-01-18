<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Model;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 
 */

/**
 * Cookie
 */
class Cookie extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * httpOnly
     *
     * @var int
     */
    protected $httpOnly = 0;

    /**
     * domain
     *
     * @var string
     */
    protected $domain = '';

    /**
     * secure
     *
     * @var int
     */
    protected $secure = 0;

    /**
     * path
     *
     * @var string
     */
    protected $path = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

    /**
     * expiry
     *
     * @var int
     */
    protected $expiry = 0;

    /**
     * isRegex
     *
     * @var bool
     */
    protected $isRegex = false;

    /**
     * serviceIdentifier
     *
     * @var string
     */
    protected $serviceIdentifier = '';

    /**
     * Returns the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns the httpOnly
     *
     * @return int
     */
    public function getHttpOnly()
    {
        return $this->httpOnly;
    }

    /**
     * Sets the httpOnly
     *
     * @param int $httpOnly
     * @return void
     */
    public function setHttpOnly(int $httpOnly)
    {
        $this->httpOnly = $httpOnly;
    }

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
     * Returns the secure
     *
     * @return int
     */
    public function getSecure()
    {
        return $this->secure;
    }

    /**
     * Sets the secure
     *
     * @param int $secure
     * @return void
     */
    public function setSecure(int $secure)
    {
        $this->secure = $secure;
    }

    /**
     * Returns the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Sets the path
     *
     * @param string $path
     * @return void
     */
    public function setPath(string $path)
    {
        $this->path = $path;
    }

    /**
     * Returns the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Returns the expiry
     *
     * @return int
     */
    public function getExpiry()
    {
        return $this->expiry;
    }

    /**
     * Sets the expiry
     *
     * @param int $expiry
     * @return void
     */
    public function setExpiry(int $expiry)
    {
        $this->expiry = $expiry;
    }

    /**
     * Returns the isRegex
     *
     * @return bool
     */
    public function getIsRegex()
    {
        return $this->isRegex;
    }

    /**
     * Sets the isRegex
     *
     * @param bool $isRegex
     * @return void
     */
    public function setIsRegex(bool $isRegex)
    {
        $this->isRegex = $isRegex;
    }

    /**
     * Returns the boolean state of isRegex
     *
     * @return bool
     */
    public function isIsRegex()
    {
        return $this->isRegex;
    }

    /**
     * Returns the serviceIdentifier
     *
     * @return string
     */
    public function getServiceIdentifier()
    {
        return $this->serviceIdentifier;
    }

    /**
     * Sets the serviceIdentifier
     *
     * @param string $serviceIdentifier
     * @return void
     */
    public function setServiceIdentifier(string $serviceIdentifier)
    {
        $this->serviceIdentifier = $serviceIdentifier;
    }
}
