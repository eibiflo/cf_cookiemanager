<?php


namespace CodingFreaks\CfCookiemanager\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use CodingFreaks\CfCookiemanager\Event\ClassifyContentEvent;

/**
 * Class RenderUtility
 * @package CodingFreaks\CfCookiemanager\Utility
 *
 * TODO: Refactor this class to use a PSR-14 EventDispatcher and refactored to improve readability and maintainability. getDomAttributes, getInlineStyle...
 */
class RenderUtility
{

    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * check if string contains valid html
     *
     * @param string $html
     * @return boolean
     */
    function isHTML($html)
    {
        // remove all scripts and iframes if found in HTML content
        if ($html != strip_tags($html) && (strpos($html, '<iframe') !== false || strpos($html, '<script') !== false)) {
            return true;  // if string is HTML
        } else {
            return false; // if string is not HTML
        }
    }

    /**
     * Classify Content by Searching for Iframes and Scripts get URLs and find the Service, if not Found Return false
     *
     * @param string providerURL
     * @return mixed
     */
    public function classifyContent($providerURL)
    {

        /** @var ClassifyContentEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new ClassifyContentEvent($providerURL)
        );
        if (!empty($event)) {
            $serviceIdentifierFromPSR14 = $event->getServiceIdentifier();
            if (!empty($serviceIdentifierFromPSR14)) {
                return $serviceIdentifierFromPSR14;
            }
        }


        /* @deprecated Call the hook classifyContent */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/cf-cookiemanager']['classifyContent'] ?? [] as $_funcRef) {
            $params = ["providerURL" => $providerURL];
            $test = GeneralUtility::callUserFunction($_funcRef, $params, $this);
            if (!empty($test)) {
                return $test;
            }
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_cfcookiemanager_domain_model_cookieservice');
        $queryBuilder->select('provider', 'identifier')
            ->from('tx_cfcookiemanager_domain_model_cookieservice', 'service')
            ->innerJoin(
                'service',
                'tx_cfcookiemanager_cookiecartegories_cookieservice_mm',
                'mm',
                $queryBuilder->expr()->eq('mm.uid_foreign', 'service.uid')
            )
            ->where(
                $queryBuilder->expr()->isNotNull('mm.uid_local')
            );
        $servicesDB = $queryBuilder->executeQuery()->fetchAllAssociative();
        foreach ($servicesDB as $service) {
            if (!empty($service["provider"])) {
                $providers = explode(",", $service["provider"]);
                foreach ($providers as $provider) {
                    if (str_contains($providerURL, $provider)) {
                        //Content Blocker Found a Match
                        //IF FORCE BLOCK RETURN NOW.
                        //DebuggerUtility::var_dump($service["identifier"]);
                        return $service["identifier"];
                    }
                }
            }
        }

        return false;
    }

    /**
     * This function renders the Scriptblocker template using Fluid StandaloneView and returns the HTML output.
     * @param array $variables An associative array of variables to be assigned to the template.
     * @return string The rendered HTML output.
     * @todo use this function to render the Consent Themes, check compatibility with the current implementation
     *
     */
    public function getTemplateHtml(array $variables = array())
    {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $tempView */
        $tempView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $templateRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName("EXT:cf_cookiemanager/Resources/Static/scriptblocker.html");

        if (!empty($extensionConfiguration["CF_SCRIPTBLOCKER"]) && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SCRIPTBLOCKER"]))) {
            $templateRootPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SCRIPTBLOCKER"]);
        }

        $tempView->setTemplatePathAndFilename($templateRootPath);

        $tempView->assignMultiple($variables);
        $tempHtml = $tempView->render();

        return $tempHtml;
    }

    /**
     * Replaces iframes in the given content string with a div containing a template, based on certain conditions.
     *
     * @param string $original The DOM element to be replaced.
     * @param array $attributes An associative array of the DOM element's attributes.
     * @param string $content The content string in which the replacement should occur.
     * @return string The content string with the iframes replaced.
     */
    public function iframeBlockerRegex($original, $attributes, $content)
    {
        if (!empty($attributes["src"])) {
            $iframe_host = parse_url($attributes["src"], PHP_URL_HOST);
            if (!empty($attributes["data-script-blocking-disabled"]) && $attributes["data-script-blocking-disabled"] == "true") {
                return $content; //Return the same content, because the script is enabled by data tag
            }

            $current_host = $_SERVER['HTTP_HOST'];
            if ($iframe_host !== $current_host) {
                // Replace the current iframe with the replacement string
                $content = str_replace($original, '<div>'.$this->getTemplateHtml(["host" => $iframe_host, "src" => $attributes["src"]]).'</div>', $content);
            }
        }
        return $content;
    }

    /**
     * Main Hook for render Function to Classify and Protect Output Content from CMS
     *
     * @param string $content
     * @param array $extensionConfiguration
     * @return string
     */
    public function cfHook($content, $extensionConfiguration): string
    {
        $newContent = $this->replaceIframes($content, $extensionConfiguration);
        $newContent = $this->replaceScript($newContent, $extensionConfiguration);
        return $newContent;
    }

    /**
     * Extracts all attributes from a specified HTML element in a given content string.
     *
     * @param string $content The content string to search for the HTML element.
     * @param string $element The name of the HTML element to extract attributes from.
     * @return array An associative array where the keys are the attribute names and the values are the attribute values.
     */
    function getHtmlElementAttributes($content, $element) {
        // Regex pattern to match the HTML element
        $elementPattern = '/<' . $element . '[^>]*>/is';

        // Find all elements in the content
        preg_match_all($elementPattern, $content, $elementMatches);

        $attributes = [];

        // Loop through all element matches
        foreach ($elementMatches[0] as $elementMatch) {
            // Regex pattern to match attributes
            $attributePattern = '/([a-zA-Z-]+)="([^"]*)"/i';

            // Find all attributes in the element
            preg_match_all($attributePattern, $elementMatch, $attributeMatches, PREG_SET_ORDER);

            // Loop through all attribute matches
            foreach ($attributeMatches as $attributeMatch) {
                // Add the attribute to the array
                $attributes[$attributeMatch[1]] = $attributeMatch[2];
            }
        }

        return $attributes;
    }

    /**
     *  Experimental Function to replace Iframes with Divs
     *  The issue is/was that every HTML parser alters the HTML in a way that doesn't match the original. Sometimes the doctype is missing, sometimes closing tags are added that shouldn't be there, and SVG also causes problems, or attributes are completed.
     *  I'm already considering approaching the entire thing differently by not saving the DOM anymore. Instead, I would temporarily read the real DOM to find elements more easily, and replace the HTML directly in the real DOM by using regex.
     */
    public function replaceIframes($content, $extensionConfiguration): string
    {
        if (!$this->isHTML($content)) {
            return $content;
        }

        // Regex pattern to match iframes
        ///<iframe[^>]*?(?:\/>|>[^<]*?<\/iframe>)/i
        $pattern = '/<iframe[^>]*>(.*?)<\/iframe>/is';
        // Find all iframes in the content
        preg_match_all($pattern, $content, $matches);

        if (!empty($matches[0])) {

            foreach ($matches[0] as $iframe) {
                $attributes = $this->getHtmlElementAttributes($iframe, 'iframe');
                if (empty($attributes["src"])) {
                    //Ignore inline Scripts without Source
                    continue;
                }

                // if "unknown" as service, it will be a empty black box
                $serviceIdentifier = $this->classifyContent($attributes["src"]);

                if (empty($serviceIdentifier)) {
                    if (intval($extensionConfiguration["scriptBlocking"]) === 1) {
                        //Script Blocking is enabled so Block all Scripts and Iframes
                        $content = $this->iframeBlockerRegex($iframe, $attributes, $content);
                    }
                } else {
                    $inlineStyle = '';
                    if (isset($attributes["height"])) {
                        $inlineStyle .= strpos($attributes["height"], 'px') !== false ? "height:{$attributes["height"]}; " : "height:{$attributes["height"]}px; ";
                    }
                    if (isset($attributes["width"])) {
                        $inlineStyle .= strpos($attributes["width"], 'px') !== false ? "width:{$attributes["width"]}; " : "width:{$attributes["width"]}px; ";
                    }
                    $inlineStyle = isset($attributes["style"]) ? htmlentities($attributes["style"], ENT_QUOTES, 'UTF-8') . $inlineStyle : $inlineStyle;

                    // Create new div element with sanitized attributes
                    $div = '<div';
                    $div .= ' style="' . $inlineStyle . '"';
                    $div .= ' data-service="' . htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8') . '"';
                    $div .= ' data-id="' . $attributes["src"] . '"';
                    $div .= ' data-autoscale=""';
                    $div .= '></div>';

                    $content = str_replace($iframe, $div, $content);
                }
            }
        }
        return $content;
    }

    /**
     * add Attribute in HTML Tag...
     *
     * for Ex:- $htmlStr = <a href="https://coding-freaks.com">https://coding-freaks.com/</a> , $tagName = a, $attributeName = target, $attributevalue = _blank
     * output will :- <a href="https://coding-freaks.com" target="_blank">coding-freaks.com</a>
     *
     * then above $htmlStr = #above output, $tagName = a, $attributeName = style, $attributevalue = color:red;
     * output will :- <a href="https://coding-freaks.com" target="_blank" style="color:red;">coding-freaks.com</a>
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
        /** if html tag attribute does not exist then add it ... */
        if (!preg_match("~<$tagname\s.*?$attributeName=([\'\"])~i", $htmlStr)) {
            $htmlStr = preg_replace('/(<' . $tagname . '\b[^><]*)>/i', '$1 ' . $attributeName . '="' . $attributeValue . '">', $htmlStr, 1);
        } else {
            // If the attribute already exists, replace its value
            $htmlStr = preg_replace("~(<$tagname\s.*?$attributeName=)([\'\"])(.*?)([\'\"])~i", '$1$2' . $attributeValue . '$4', $htmlStr, 1);
        }
        return $htmlStr;
    }

    /**
     * Replaces script tags in the given content string based on certain conditions.
     *
     * @param string $content The content string in which the replacement should occur.
     * @param array $extensionConfiguration The configuration options for the extension.
     * @return string The content string with the script tags replaced.
     */
    public function replaceScript($content, $extensionConfiguration): string
    {
        if (!$this->isHTML($content)) {
            return $content;
        }

        // Regex pattern to match scripts
        $pattern = '/<script[^>]*>(.*?)<\/script>/is';

        // Find all scripts in the content
        preg_match_all($pattern, $content, $matches);

        if(!empty($matches[0])){
            foreach ($matches[0] as $script) {
                $attributes = $this->getHtmlElementAttributes($script, 'script');

                if (empty($attributes["src"])) {
                    continue;
                }

                $serviceIdentifier = $this->classifyContent($attributes["src"]);

                $urlEmbeded = $attributes["src"];
                // Parse the URL to ignore the GET parameters
                if (!empty(parse_url($attributes["src"], PHP_URL_HOST))) {
                    $urlEmbeded = parse_url($attributes["src"], PHP_URL_HOST) . parse_url($attributes["src"], PHP_URL_PATH);
                }

                if (empty($serviceIdentifier)) {
                    if (intval($extensionConfiguration["scriptBlocking"]) === 1) {
                        if (!empty($attributes['data-script-blocking-disabled']) && $attributes['data-script-blocking-disabled'] == "true") {
                            //Script is not modified, return the same content because blocking is disabled by data tag
                        }else{
                            //Should we use Templates here? or just remove the script tag?
                            $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($script, 'script', 'type', 'text/plain');
                            $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($modifiedScriptTag, 'script', 'data-service', "unknown");

                            // Replace the original script tag with the modified script tag in the content
                            $content = str_replace($script, $modifiedScriptTag, $content);
                        }

                    }
                } else {

                    if (strpos($script, $urlEmbeded) !== false) {
                        //Script is not replaced, replace it
                        $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($script, 'script', 'type', 'text/plain');
                        $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($modifiedScriptTag, 'script', 'data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));

                        // Replace the original script tag with the modified script tag in the content
                        $content = str_replace($script, $modifiedScriptTag, $content);
                    }

                }
            }
        }

        return $content;
    }
}
