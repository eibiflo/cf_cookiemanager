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
            $url = $extensionConfiguration["endPoint"] . $endPoint ."/" . $lang;

            /*
             * Not sure if we really need this check
                        if (filter_var($url, FILTER_VALIDATE_URL) === false || @get_headers($url,true,    stream_context_create([
                                    'http' => ['ignore_errors' => true,  'method' => 'HEAD', 'timeout' => 5,'header' => 'User-Agent: CF-TYPO3-Extension'],
                            ])) === false) {
                            // The URL is not valid or not accessible.
                            return [];
                        }
            */

            $context = stream_context_create([
                'http' => ['ignore_errors' => true, 'timeout' => 15,'header' => 'User-Agent: CF-TYPO3-Extension'],
            ]);
            $json = @file_get_contents($url, false, $context);
            if($json === false) {
                return [];
            }
            $services = json_decode($json, true);
            return $services;
        }
        return [];
    }

    /**
     * This function fetches data from a local JSON file based on the provided language code.
     * The JSON file is located in the Data folder of the cf_cookiemanager extension.
     *
     * @param string $lang The language code (e.g., 'en', 'de') for which categories will be fetched from the JSON file.
     * @param string $endPoint The name of the JSON file without the .json extension.
     * @return array An array retrieved from the JSON file.
     */
    public function callFile($lang, $endPoint)
    {
        // Define the path to the Data folder and the JSON file
        $filePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cf_cookiemanager') . 'Resources/Static/Data/' . $endPoint . '/' . $lang . '.json';

        // Check if the JSON file exists
        if (file_exists($filePath)) {
            // If the JSON file exists, read the file
            $json = file_get_contents($filePath);
            // Decode the JSON data
            $services = json_decode($json, true);
            // Return the decoded JSON data
            return $services;
        }

        // If the JSON file does not exist, return an empty array
        return [];
    }


}
