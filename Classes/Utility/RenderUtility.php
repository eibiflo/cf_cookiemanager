<?php


namespace  CodingFreaks\CfCookiemanager\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use Psr\EventDispatcher\EventDispatcherInterface;
use CodingFreaks\CfCookiemanager\Event\ClassifyContentEvent;
use Masterminds\HTML5;
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
    function isHTML($html){
        // remove all scripts and iframes if found in HTML content
        if($html != strip_tags($html) && (strpos($html, '<iframe') !== false || strpos($html, '<script') !== false)){
            return true;  // if string is HTML
        }else{
            return false; // if string is not HTML
        }
    }


    /**
     * Ensures the HTML content is saved as UTF-8.
     *
     * This function addresses an issue with the DOMDocument::saveHTML() method, which does not save the HTML content as UTF-8.
     * Instead, it converts certain characters into HTML entities. For instance, the word 'שלום' would be converted into '&#1513;&#1500;&#1493;&#1501;'.
     * ÄÖÜ would be converted into &Auml;&Ouml;&Uuml;....
     *
     * The goal of this function is to prevent the DOMDocument::saveHTML() method from converting characters into HTML entities.
     * However, it's important to note that the html_entity_decode function cannot be used on all DOMDocument nodes.
     * Because it will convert all HTML entities into their applicable characters.
     *
     * For more information on this issue, refer to the following resources:
     * - https://stackoverflow.com/questions/8218230/php-domdocument-loadhtml-not-encoding-utf-8-correctly
     * - https://php-general.php.narkive.com/yLNZ0pMf/php-dom-savehtml-outputs-entities
     *
     * @param string $html The HTML content to be saved.
     * @return string The HTML content saved as UTF-8.
     */
    public function htmlUTF8Save($html)
    {
        if (version_compare(PHP_VERSION, '8.2', '>=')) {
            /* Since mb_convert_encoding() is deprecated in PHP 8.2, we need to use mb_encode_numericentity() instead.
             * [0x80, 0x10FFFF, 0, ~0] is an array specifying the range of characters to convert. In this case, it's specifying a range from 0x80 to 0x10FFFF.
             * The third and fourth elements of the array are used to set a mask for the specified range. Here, 0 and ~0 mean that all characters in the specified range will be converted.
             */
            return mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, ~0], 'UTF-8');
        } else {
            return mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        }
    }


    /**
     * Find and replace a script tag and override the attribute to text/plain
     *
     * @param string $html
     * @param string $databaseRow
     * @param array $extensionConfiguration
     * @return string
     */
    public function overrideScript($html, $databaseRow, $extensionConfiguration): string
    {
        if(!$this->isHTML($html)){
            return $html;
        }

        $html5 = new HTML5(['disable_html_ns' => true]);
        $dom = $html5->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script');
        foreach ($scripts as $script) {
            $attributes = array();
            foreach ($script->attributes as $attr) {
                // Validate and sanitize attribute values
                $attrValue = htmlentities($attr->value, ENT_QUOTES, 'UTF-8');
                $attributes[$attr->name] = $attrValue;
            }
            if(empty($attributes["src"])){
                //Skip inline Scripts
                continue;
            }

            $serviceIdentifier = $this->classifyContent($attributes["src"]);
            if(empty($serviceIdentifier)){
                if(intval($extensionConfiguration["scriptBlocking"]) === 1){
                    //Script Blocking is enabled so Block all Scripts and Iframes
                    if(empty($attributes["data-script-blocking-disabled"]) || (!empty($attributes["data-script-blocking-disabled"]) && $attributes["data-script-blocking-disabled"] !== "true")){
                        $script->setAttribute('type', "text/plain");
                    }
                }
            }
            if(!empty($serviceIdentifier)){
                $script->setAttribute('data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));
                $script->setAttribute('type', "text/plain");
            }
        }

        return $dom->saveHTML($dom);
    }

    /**
     * Find and replace a iframe and override it with a Div to Inject iFrameManager in Frontend
     *
     * @param string $html
     * @param string $databaseRow
     * @param array $extensionConfiguration
     * @return string
     */
    public function overrideIframes($html,$databaseRow,$extensionConfiguration): string
    {

        if(!$this->isHTML($html)){
            return $html;
        }

        $html5 = new HTML5(['disable_html_ns' => true]);
        $dom = $html5->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $iframes = $xpath->query('//iframe');

        foreach ($iframes as $iframe) {
            $attributes = array();
            foreach ($iframe->attributes as $attr) {
                //Validate and sanitize attribute values
                //$attrValue = htmlentities($attr->value, ENT_NOQUOTES, 'UTF-8'); Removed because GET Parameter in Iframes are Quoted and Failed to Load
                $attributes[$attr->name] = $attr->value;
            }

            if(empty($attributes["src"])) {
                //Ignore inline Scripts without Source
                continue;
            }


            // if "unknown" as service, it will be a empty black box
            $serviceIdentifier = $this->classifyContent($attributes["src"]);

            if(empty($serviceIdentifier)){
                if(intval($extensionConfiguration["scriptBlocking"]) === 1){
                    //Script Blocking is enabled so Block all Scripts and Iframes
                    $this->scriptBlocker($iframe,$dom);
                    //$iframe->parentNode->replaceChild($div, $iframe);
                }
            }else{
                $inlineStyle = '';
                if (isset($attributes["height"])) {
                    $inlineStyle .= strpos($attributes["height"], 'px') !== false ? "height:{$attributes["height"]}; " : "height:{$attributes["height"]}px; ";
                }
                if (isset($attributes["width"])) {
                    $inlineStyle .= strpos($attributes["width"], 'px') !== false ? "width:{$attributes["width"]}; " : "width:{$attributes["width"]}px; ";
                }
                $inlineStyle = isset($attributes["style"]) ? htmlentities($attributes["style"], ENT_QUOTES, 'UTF-8') . $inlineStyle : $inlineStyle;

                // Create new div element with sanitized attributes
                $div = $dom->createElement('div');
                $div->setAttribute('style', $inlineStyle);
                $div->setAttribute('data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));
                $div->setAttribute('data-id', $attributes["src"]);
                $div->setAttribute('data-autoscale', "");
                // Replace iframe element with new div element
                $iframe->parentNode->replaceChild($div, $iframe);
            }
        }

        return $dom->saveHTML($dom);
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
        if(!empty($event)){
            $serviceIdentifierFromPSR14 = $event->getServiceIdentifier();
            if(!empty($serviceIdentifierFromPSR14)){
                return $serviceIdentifierFromPSR14;
            }
        }


        /* @deprecated Call the hook classifyContent */
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/cf-cookiemanager']['classifyContent'] ?? [] as $_funcRef) {
            $params = ["providerURL"=>$providerURL];
            $test =   GeneralUtility::callUserFunction($_funcRef, $params, $this);
            if(!empty($test)){
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

        return  false;
    }

    /**
     * This function renders the Scriptblocker template using Fluid StandaloneView and returns the HTML output.
     * @todo use this function to render the Consent Themes, check compatibility with the current implementation
     *
     * @param array $variables An associative array of variables to be assigned to the template.
     * @return string The rendered HTML output.
     */
    public function getTemplateHtml(array $variables = array()) {
        /** @var \TYPO3\CMS\Fluid\View\StandaloneView $tempView */
        $tempView = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);

        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');
        $templateRootPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName("EXT:cf_cookiemanager/Resources/Static/scriptblocker.html");

        if(!empty($extensionConfiguration["CF_SCRIPTBLOCKER"]) && file_exists(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SCRIPTBLOCKER"]))) {
            $templateRootPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::resolvePackagePath($extensionConfiguration["CF_SCRIPTBLOCKER"]);
        }

        $tempView->setTemplatePathAndFilename($templateRootPath);

        $tempView->assignMultiple($variables);
        $tempHtml = $tempView->render();

        return $tempHtml;
    }

    /**
     * Prevents the loading of content such as iframes and scripts from third-party sources, can be Disabled by adding a Data Atribute to the Script or Iframe (data-script-blocking-disabled="true")
     *
     * @param \DOMElement $domElement The HTML content to be checked.
     * @return void|false The "modified" HTML content or an error message if the content was blocked.
     */
    public function scriptBlocker($domElement,$doc){


        if(!empty($domElement->getAttribute("src"))) {
            $iframe_host = parse_url($domElement->getAttribute("src"), PHP_URL_HOST);
            $scriptBlockingTag = $domElement->getAttribute('data-script-blocking-disabled');
            if(!empty($scriptBlockingTag) && $scriptBlockingTag == "true"){
                return false;
            }
            $current_host = $_SERVER['HTTP_HOST'];
            if($iframe_host !== $current_host){
                $div = $doc->createDocumentFragment();
                $div->appendXML($this->getTemplateHtml(["host"=>$iframe_host,"src"=>$domElement->getAttribute("src")]));

                // Replace iframe element with new div element
                $domElement->parentNode->replaceChild($div, $domElement);
            }
        }
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
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('cf_cookiemanager');

        $newContent = $this->overrideIframes($content,$databaseRow,$extensionConfiguration);
        $newContent = $this->overrideScript($newContent,$databaseRow,$extensionConfiguration);
        return $newContent;
    }
}
