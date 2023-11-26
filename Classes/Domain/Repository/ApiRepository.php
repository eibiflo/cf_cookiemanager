<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Repository;


use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * The repository to Handle API Calls
 */
class ApiRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{

    /**
     *
     * This function fetches data from an external API endpoint based on the provided language code.
     * The API endpoint is obtained from the cf_cookiemanager extension's configuration.
     *
     * @param string $lang The language code (e.g., 'en', 'de') for which categories will be fetched from the API.
     * @param string $endPoint The API endpoint to call.
     * @return array An array retrieved from the API.
     *
     */
    public function callAPI($lang,$endPoint)
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        if (!empty($extensionConfiguration["endPoint"])) {
            $context = stream_context_create(array(
                'http' => array('ignore_errors' => true),
            ));

            $json = file_get_contents($extensionConfiguration["endPoint"] . $endPoint ."/" . $lang, false, $context);
            if($json === false) {
                return [];
            }
            $services = json_decode($json, true);
            return $services;
        }
        return [];
    }

}
