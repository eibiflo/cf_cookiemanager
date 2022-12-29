<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Model;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * ExternalScripts
 */
class ExternalScripts extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * link
     *
     * @var string
     */
    protected $link = '';

    /**
     * async
     *
     * @var bool
     */
    protected $async = false;

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
     * Returns the link
     *
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * Sets the link
     *
     * @param string $link
     * @return void
     */
    public function setLink(string $link)
    {
        $this->link = $link;
    }

    /**
     * Returns the async
     *
     * @return bool
     */
    public function getAsync()
    {
        return $this->async;
    }

    /**
     * Sets the async
     *
     * @param bool $async
     * @return void
     */
    public function setAsync(bool $async)
    {
        $this->async = $async;
    }

    /**
     * Returns the boolean state of async
     *
     * @return bool
     */
    public function isAsync()
    {
        return $this->async;
    }
}
