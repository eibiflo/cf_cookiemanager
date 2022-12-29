<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * The repository for Variables
 */
class VariablesRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     * @param $identifier
     * @param $value
     * @param $content
     */
    public function replace($identifier, $value, $content)
    {
        return str_replace("[##" . $identifier . "##]", $value, $content);
    }

    /**
     * @param $content
     * @param $allVariables
     */
    public function replaceVariable($content, $allVariables)
    {
        foreach ($allVariables as $variable) {
            $content = $this->replace($variable->getIdentifier(), $variable->getValue(), $content);
        }
        return $content;
    }
}
