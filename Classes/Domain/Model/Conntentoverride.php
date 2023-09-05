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
 * @deprecated since 1.3.x
 * Conntentoverride
 */
class Conntentoverride extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * contentlink
     *
     * @var string
     */
    protected $contentlink = '';

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
     * Returns the contentlink
     *
     * @return string
     */
    public function getContentlink()
    {
        return $this->contentlink;
    }

    /**
     * Sets the contentlink
     *
     * @param string $contentlink
     * @return void
     */
    public function setContentlink(string $contentlink)
    {
        $this->contentlink = $contentlink;
    }
}
