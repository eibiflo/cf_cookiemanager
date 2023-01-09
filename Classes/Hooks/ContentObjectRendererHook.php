<?php

namespace CodingFreaks\CfCookiemanager\Hooks;


/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Configuration\Features;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DocumentTypeExclusionRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Html\SanitizerBuilderFactory;
use TYPO3\CMS\Core\Html\SanitizerInitiator;
use TYPO3\CMS\Core\Imaging\ImageManipulation\Area;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use TYPO3\CMS\Core\LinkHandling\Exception\UnknownLinkHandlerException;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Page\DefaultJavaScriptAssetTrait;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\ContentObject\AbstractContentObject;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;
use TYPO3\CMS\Frontend\ContentObject\Exception\ExceptionHandlerInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\UrlProcessorInterface;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Frontend\Page\PageLayoutResolver;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\CMS\Frontend\Service\TypoLinkCodecService;
use TYPO3\CMS\Frontend\Typolink\AbstractTypolinkBuilder;
use TYPO3\CMS\Frontend\Typolink\LinkResult;
use TYPO3\CMS\Frontend\Typolink\LinkResultInterface;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;
use TYPO3\HtmlSanitizer\Builder\BuilderInterface;

class ContentObjectRendererHook extends \TYPO3\CMS\Frontend\ContentObject\ContentContentObject
{

    /**
     * add Attribute in HTML Tag...
     *
     * for Ex:- $htmlStr = <a href="http://saveprice.in">http://saveprice.in/</a> , $tagName = a, $attributeName = target, $attributevalue = _blank
     * output will :- <a href="http://saveprice.in" target="_blank">saveprice.in</a>
     *
     * then above $htmlStr = #above output, $tagName = a, $attributeName = style, $attributevalue = color:red;
     * output will :- <a href="http://saveprice.in" target="_blank" style="color:red;">saveprice.in</a>
     *
     * @param string $htmlStr // html string
     * @param string $tagname // html tag name
     * @param string $attributeName // html tag attribute name like class, id, style etc...
     * @param string $attributeValue // value of attribute like, classname, idname, style-property etc...
     *
     * @return string
     */
    public function addHtmlAttribute_in_HTML_Tag($htmlStr, $tagname, $attributeName, $attributeValue): string
    {
        if (!preg_match("~<$tagname\s.*?$attributeName=([\'\"])~i", $htmlStr)) {
            // if html tag attribute does not exist then add it ...
            $htmlStr = preg_replace('/(<' . $tagname . '\b[^><]*)>/i', '$1 ' . $attributeName . '="' . $attributeValue . '">', $htmlStr, 1);
        }
        return $htmlStr;
    }


    /**
     * Get  video ID from URL
     *
     * @param string $url
     * @return mixed  video ID or FULL URL if not found
     */
    function getVideoIdFromUrl($url, $identifier)
    {
        $parts = parse_url($url);
        if ($identifier == "vimeo" || $identifier == "youtube") {
            if (isset($parts['query'])) {
                parse_str($parts['query'], $qs);
                if (isset($qs['v'])) {
                    return $qs['v'];
                } else if (isset($qs['vi'])) {
                    return $qs['vi'];
                }
            }
            if (isset($parts['path'])) {
                $path = explode('/', trim($parts['path'], '/'));
                return $path[count($path) - 1];
            }
        }

        return $url;
    }


    /**
     * Find and replace a iframe and override it with a Div to Inject iFrameManager in Frontend
     *
     * @param string $html
     * @return string
     */
    public function overrideIframe($html) : string
    {
        //TODO Dynamic maper from Service DB
        $mapper = [
            "google.com/maps/embed" => "googlemaps",
            "player.vimeo.com/video/" => "vimeo",
            "youtube.com/" => "youtube",
            "youtube-nocookie.com/" => "youtube",
            "soundcloud.com/" => "soundcloud",
            "e.issuu.com/" => "issuu",
        ];


        $override = "";
        preg_match('/<iframe.*src=\"(.*)\".*><\/iframe>/isU', $html, $matches);
        $url = $matches[1];
        foreach ($mapper as $search => $identifier) {
            if (str_contains($url, $search)) {
                // <!-- Responsive iframe with custom title + custom thumbnail -->
                $override = '
                    <div
                        data-service="' . $identifier . '"
                        data-id="' . $url . '"
                        data-thumbnail=""
                        data-autoscale>
                    </div>
                ';

            }
        }

        $res = preg_replace('/<iframe.*src=\"(.*)\".*><\/iframe>/', $override, $html);


        return $res;
    }

    /**
     * Classify Content in a HTML string
     *
     * @param string $content
     * @param string $databaseRow
     * @return string
     */
    public function classifyContent($content, $dbRow): string
    {
        preg_match("/<iframe.*src=\"(.*)\".*><\/iframe>/", $content, $detectedIframes);
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookieservice');
        $this->queryBuilder->select('provider', "identifier")->from('tx_cfcookiemanager_domain_model_cookieservice');
        //echo($this->queryBuilder->getSQL());
        $result = $this->queryBuilder->executeQuery()->fetchAllAssociative();
        foreach ($result as $service) {
            if (!empty($service["provider"])) {
                $providers = explode(",", $service["provider"]);
                $iframeURL = $detectedIframes[1];
                foreach ($providers as $provider) {
                    if (str_contains($iframeURL, $provider)) {
                        //Content Blocker Found a Match
                        return $service["identifier"];
                    }
                }
            }
        }


        return false;
    }


    /**
     * Main Hook for render Function to Classify and Protect Output Content from CMS
     *
     * @param string $content
     * @param string $databaseRow
     * @return string
     */
    public function cfHook($content, $databaseRow) : string
    {

        $serviceIdentifier = $this->classifyContent($content, $databaseRow);
        /*
                $con = \CodingFreaks\CfCookiemanager\Utility\HelperUtility::getDatabase();
                $sqlStr = "SELECT tx_cfcookiemanager_domain_model_cookiecartegories.identifier, tx_cfcookiemanager_domain_model_cookieservice.name
                            FROM `tx_cfcookiemanager_domain_model_cookieservice`
                                     LEFT JOIN tx_cfcookiemanager_cookiecartegories_cookieservice_mm
                                               on tx_cfcookiemanager_domain_model_cookieservice.uid =
                                                  tx_cfcookiemanager_cookiecartegories_cookieservice_mm.uid_foreign
                                     LEFT JOIN tx_cfcookiemanager_domain_model_cookiecartegories on tx_cfcookiemanager_domain_model_cookiecartegories.uid = tx_cfcookiemanager_cookiecartegories_cookieservice_mm.uid_local

                                    WHERE tx_cfcookiemanager_domain_model_cookieservice.identifier = '$serviceIdentifier'
                                    ";
                $results = $con->executeQuery($sqlStr)->fetchAssociative();
                $category = $results["identifier"];
        */

        if (!empty($serviceIdentifier)) {
            //$newContentTmp =  $this->addHtmlAttribute_in_HTML_Tag($content,"div","data-category",$category);
            $newContent = $this->addHtmlAttribute_in_HTML_Tag($content, "div", "data-cookiecategory", $serviceIdentifier);
            return $this->overrideIframe($newContent);
        }

        return $content;
    }

    /**
     * Rendering the cObject, CONTENT
     *
     * @param array $conf Array of TypoScript properties
     * @return string Output
     */
    public function render($conf = []) : string
    {

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');


        if (!empty($conf['if.']) && !$this->cObj->checkIf($conf['if.'])) {
            return '';
        }

        $frontendController = $this->getTypoScriptFrontendController();
        $theValue = '';
        $originalRec = $frontendController->currentRecord;
        // If the currentRecord is set, we register, that this record has invoked this function.
        // It should not be allowed to do this again then!!
        if ($originalRec) {
            if (isset($frontendController->recordRegister[$originalRec])) {
                ++$frontendController->recordRegister[$originalRec];
            } else {
                $frontendController->recordRegister[$originalRec] = 1;
            }
        }
        $conf['table'] = trim((string)$this->cObj->stdWrapValue('table', $conf ?? []));
        $conf['select.'] = !empty($conf['select.']) ? $conf['select.'] : [];
        $renderObjName = ($conf['renderObj'] ?? false) ? $conf['renderObj'] : '<' . $conf['table'];
        $renderObjKey = ($conf['renderObj'] ?? false) ? 'renderObj' : '';
        $renderObjConf = $conf['renderObj.'] ?? [];


        $slide = (int)$this->cObj->stdWrapValue('slide', $conf ?? []);
        if (!$slide) {
            $slide = 0;
        }
        $slideCollect = (int)$this->cObj->stdWrapValue('collect', $conf['slide.'] ?? []);
        if (!$slideCollect) {
            $slideCollect = 0;
        }
        $slideCollectReverse = (bool)$this->cObj->stdWrapValue('collectReverse', $conf['slide.'] ?? []);
        $slideCollectFuzzy = (bool)$this->cObj->stdWrapValue('collectFuzzy', $conf['slide.'] ?? []);
        if (!$slideCollect) {
            $slideCollectFuzzy = true;
        }
        $again = false;
        $tmpValue = '';

        do {
            $records = $this->cObj->getRecords($conf['table'], $conf['select.']);

            $cobjValue = '';
            if (!empty($records)) {
                // @deprecated since v11, will be removed in v12. Drop together with ContentObjectRenderer->currentRecordTotal
                $this->cObj->currentRecordTotal = count($records);
                $this->getTimeTracker()->setTSlogMessage('NUMROWS: ' . count($records));

                $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class, $frontendController);
                $cObj->setParent($this->cObj->data, $this->cObj->currentRecord);
                $this->cObj->currentRecordNumber = 0;

                foreach ($records as $row) {
                    // Call hook for possible manipulation of database row for cObj->data
                    foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content_content.php']['modifyDBRow'] ?? [] as $className) {
                        $_procObj = GeneralUtility::makeInstance($className);
                        $_procObj->modifyDBRow($row, $conf['table']);
                    }
                    $registerField = $conf['table'] . ':' . ($row['uid'] ?? 0);
                    if (!($frontendController->recordRegister[$registerField] ?? false)) {
                        $this->cObj->currentRecordNumber++;
                        $cObj->parentRecordNumber = $this->cObj->currentRecordNumber;
                        $frontendController->currentRecord = $registerField;
                        $this->cObj->lastChanged($row['tstamp'] ?? 0);
                        $cObj->start($row, $conf['table'], $this->request);

                        $tmpValue = $cObj->cObjGetSingle($renderObjName, $renderObjConf, $renderObjKey);
                        if((int)$extensionConfiguration["disablePlugin"] !== 1){
                            $cobjValue .= $this->cfHook($tmpValue, $row);
                        }else{
                            $cobjValue .= $tmpValue;
                        }

                    }
                }
            }
            if ($slideCollectReverse) {
                $theValue = $cobjValue . $theValue;
            } else {
                $theValue .= $cobjValue;
            }
            if ($slideCollect > 0) {
                $slideCollect--;
            }
            if ($slide) {
                if ($slide > 0) {
                    $slide--;
                }
                $conf['select.']['pidInList'] = $this->cObj->getSlidePids(
                    $conf['select.']['pidInList'] ?? '',
                    $conf['select.']['pidInList.'] ?? [],
                );
                if (isset($conf['select.']['pidInList.'])) {
                    unset($conf['select.']['pidInList.']);
                }
                $again = (string)$conf['select.']['pidInList'] !== '';
            }
        } while ($again && $slide && ((string)$tmpValue === '' && $slideCollectFuzzy || $slideCollect));

        $wrap = $this->cObj->stdWrapValue('wrap', $conf ?? []);
        if ($wrap) {
            $theValue = $this->cObj->wrap($theValue, $wrap);
        }

        if (isset($conf['stdWrap.'])) {
            $theValue = $this->cObj->stdWrap($theValue, $conf['stdWrap.']);
        }
        // Restore
        $frontendController->currentRecord = $originalRec;
        if ($originalRec) {
            --$frontendController->recordRegister[$originalRec];
        }


        return $theValue;
    }

    /**
     * Returns Time Tracker
     *
     * @return TimeTracker
     */
    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
