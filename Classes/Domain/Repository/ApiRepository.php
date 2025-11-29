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
     * @param array $postData  Data to be sent in the POST request (optional).
     * @param array $request_headers Additional Headers (optional).
     * @return array An array retrieved from the API.
     *
     */
    public function callAPI($lang, $endPoint, $endPointURL, $postData = null, $request_headers = null)
    {
        if (!empty($endPointURL)) {
            if(!empty($lang)){
                $url = $endPointURL . $endPoint . "/" . $lang;
            }else{
                $url = $endPointURL . $endPoint;
            }


            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_USERAGENT, 'CF-TYPO3-Extension');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // disable SSL verification

            if ($postData !== null) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            }

            if($request_headers !== null){
                curl_setopt($ch, CURLOPT_HTTPHEADER, $request_headers);
            }

            $json = curl_exec($ch);

            if (curl_errno($ch)) {
                // cURL error
                curl_close($ch);
                return [];
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 400) {
                $services = json_decode($json, true);
                return $services;
            }

            if ($json === false) {
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
     * @param string $targetDirectory The target directory where the JSON file is located.
     * @return array An array retrieved from the JSON file.
     */
    public function callFile($lang, $endPoint,$targetDirectory)
    {
        // Define the path to the Data folder and the JSON file
        $filePath = $targetDirectory."/". $endPoint . '/' . $lang . '.json';

        // Check if the JSON file exists
        if (file_exists($filePath)) {
            // If the JSON file exists, read the file
            $json = file_get_contents($filePath);
            // Decode the JSON data
            $data = json_decode($json, true);
            // Return the decoded JSON data
            return $data;
        }

        // If the JSON file does not exist, return an empty array
        return [];
    }


}
