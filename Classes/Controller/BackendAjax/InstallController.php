<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Controller\BackendAjax;


use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use CodingFreaks\CfCookiemanager\Domain\Repository\ApiRepository;
use ScssPhp\ScssPhp\Formatter\Debug;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use CodingFreaks\CfCookiemanager\Service\InsertService;
use CodingFreaks\CfCookiemanager\Service\SiteService;
use CodingFreaks\CfCookiemanager\Service\CategoryLinkService;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class InstallController
{
    private $apiEndpoints = [
        "frontends",
        "categories",
        "services",
        "cookie",
    ];

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private ApiRepository                     $apiRepository,
        private InsertService                     $insertService,
        private SiteService                       $siteService,
        private CategoryLinkService               $categoryLinkService

    )
    {
    }


    public function installDatasetsAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $storageUid = intval($parsedBody['storageUid']) ?? null;
        if ($storageUid === null) {
            throw new \InvalidArgumentException('Ups an error, no storageUid provided', 1736960651);
        }
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $languages = $this->siteService->getPreviewLanguages((int)$storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiRepository->callAPI($localeShort, $apiEndpoint);

                if(empty($apiData)){
                    $response->getBody()->write(json_encode(
                        [
                            'insertSuccess' => false,
                            'error' => "API Endpoint error or not reachable, maybe firewall issues or changed Endpoint, check your Cookie Settings Configuration in Extension Settings",
                        ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord){
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid
                    ];

                    if ($apiEndpoint === "frontends") {
                        $success = $this->insertService->insertFrontends($data);
                    } else if ($apiEndpoint === "categories") {
                        $success = $this->insertService->insertCategory($data);
                    } else if ($apiEndpoint === "services") {
                        $success = $this->insertService->insertServices($data);
                    } else if ($apiEndpoint === "cookie") {
                        $success = $this->insertService->insertCookies($data);
                    }
                }


            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);


        $response->getBody()->write(json_encode(
            [
                'insertSuccess' => $success,
            ]
            , JSON_THROW_ON_ERROR));
        return $response;
    }


    public function uploadDatasetAction(ServerRequestInterface $request): ResponseInterface
    {
        $uploadedFiles = $request->getUploadedFiles();
        $datasetFile = $uploadedFiles['datasetFile'] ?? null;
        $storageUid = intval($request->getParsedBody()['storageUid']) ?? null;
        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');

        if ($datasetFile === null || $storageUid === null) {
            $response = $this->responseFactory->createResponse(400)->withHeader('Content-Type', 'application/json; charset=utf-8');
            $response->getBody()->write(json_encode(
                [
                    'uploadSuccess' => false,
                    'error' => 'Error in Request, please make a Issue on Github'
                ],
                JSON_THROW_ON_ERROR
            ));
            return $response;
        }


        // Process the uploaded file and store it in the desired location
        // Define the target directory where the file will be saved
        $typo3tempPath = GeneralUtility::getFileAbsFileName('typo3temp/');
        $targetDirectory = $typo3tempPath."cf_cookiemanager_offline/";
        if(!is_dir($targetDirectory)){
            mkdir($targetDirectory);
        }

        // File is moved successfully
        $targetFile = $targetDirectory."staticdata.zip";
        $datasetFile->moveTo($targetFile);

        //  Process the dataset file as needed
        $zip = new \ZipArchive();
        // Open the zip file
        if ($zip->open($targetFile) === TRUE) {
            // Iterate over each file in the zip file
            for($i = 0; $i < $zip->numFiles; $i++) {
                // Get the file name
                $fileName = $zip->getNameIndex($i);
                // Check if the file extension is .json
                if(pathinfo($fileName, PATHINFO_EXTENSION) === 'json') {
                    // Extract the file to the target directory
                    $zip->extractTo($targetDirectory, $fileName);
                }
            }

            // Close the zip file
            $zip->close();
            // Remove the zip file
            unlink($targetFile);
        } else {
            die("Failed to open zip file");
        }


        $languages = $this->siteService->getPreviewLanguages((int)$storageUid, $this->getBackendUser());

        $success = false;
        foreach ($this->apiEndpoints as $apiEndpoint) {
            foreach ($languages as $langKey => $language) {
                $localeShort = $language['locale-short'];

                $apiData = $this->apiRepository->callFile($localeShort, $apiEndpoint,$targetDirectory);

                if(empty($apiData)){
                    $response->getBody()->write(json_encode(
                        [
                            'insertSuccess' => false,
                            'error' => "Error in Local Dataset Installation, maybe wrong file format or missing files",
                        ], JSON_THROW_ON_ERROR));
                    return $response;
                }

                foreach ($apiData as $dataRecord){
                    $data = [
                        'entry' => $apiEndpoint,
                        'changes' => $dataRecord,
                        'languageKey' => $langKey,
                        'storage' => $storageUid
                    ];


                    if ($apiEndpoint === "frontends") {
                        $success = $this->insertService->insertFrontends($data);
                    } else if ($apiEndpoint === "categories") {
                        $success = $this->insertService->insertCategory($data);
                    } else if ($apiEndpoint === "services") {
                        $success = $this->insertService->insertServices($data);
                    } else if ($apiEndpoint === "cookie") {
                        $success = $this->insertService->insertCookies($data);
                    }
                }


            }
        }

        // Link CF-CookieManager to Required Services
        $this->categoryLinkService->addCookieManagerToRequired($languages, $storageUid);



        $response = $this->responseFactory->createResponse()->withHeader('Content-Type', 'application/json; charset=utf-8');
        $response->getBody()->write(json_encode(
            [
                'uploadSuccess' => $success,
            ],
            JSON_THROW_ON_ERROR
        ));
        return $response;
    }


    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}