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
 * CookieCartegories
 */
class CookieCartegories extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * hidden
     *
     * @var bool
     */
    protected $hidden = 0;

    /**
     * title
     *
     * @var string
     */
    protected $title = null;

    /**
     * description
     *
     * @var string
     */
    protected $description = null;

    /**
     * identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * isRequired
     *
     * @var bool
     */
    protected $isRequired = 0;

    /**
     * cookieServices
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\CookieService>
     */
    protected $cookieServices = null;

    /**
     * Returns the title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets the title
     *
     * @param string $title
     * @return void
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
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
     * __construct
     */
    public function __construct()
    {

        // Do not remove the next line: It would break the functionality
        $this->initializeObject();
    }

    /**
     * Initializes all ObjectStorage properties when model is reconstructed from DB (where __construct is not called)
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->cookieServices = $this->cookieServices ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a CookieService
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $cookieService
     * @return void
     */
    public function addCookieService(\CodingFreaks\CfCookiemanager\Domain\Model\CookieService $cookieService)
    {
        $this->cookieServices->attach($cookieService);
    }

    /**
     * Removes a CookieService
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\CookieService $cookieServiceToRemove The CookieService to be removed
     * @return void
     */
    public function removeCookieService(\CodingFreaks\CfCookiemanager\Domain\Model\CookieService $cookieServiceToRemove)
    {
        $this->cookieServices->detach($cookieServiceToRemove);
    }

    /**
     * Returns the cookieServices
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\CookieService>
     */
    public function getCookieServices()
    {
        return $this->cookieServices;
    }

    /**
     * Sets the cookieServices
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\CookieService> $cookieServices
     * @return void
     */
    public function setCookieServices(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $cookieServices)
    {
        $this->cookieServices = $cookieServices;
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
     * Returns the isRequired
     *
     * @return bool isRequired
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Sets the isRequired
     *
     * @param int $isRequired
     * @return void
     */
    public function setIsRequired(int $isRequired)
    {
        $this->isRequired = $isRequired;
    }

    /**
     * Returns the hidden field
     *
     * @return bool hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the hidden field
     *
     * @param bool $bool
     * @return void
     */
    public function setHidden(bool $bool)
    {
        $this->hidden = $bool;
    }
}
