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

/**
 * Class RenderUtility
 * @package CodingFreaks\CfCookiemanager\Utility
 *
 * TODO: Refactor this class to use a PSR-14 EventDispatcher
 * TODO: Currently overrideIframes and overrideScript are copied to new functions replaceIframes and replaceScript to test the new Regex Method, refactor this to use the same function with string replace or dom override.
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
    function isHTML($html){
        // remove all scripts and iframes if found in HTML content
        if($html != strip_tags($html) && (strpos($html, '<iframe') !== false || strpos($html, '<script') !== false)){
            return true;  // if string is HTML
        }else{
            return false; // if string is not HTML
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
    public function scriptBlockerRegex($domElement,$dom,$content){



        if(!empty($domElement->getAttribute("src"))) {
            $iframe_host = parse_url($domElement->getAttribute("src"), PHP_URL_HOST);
            $scriptBlockingTag = $domElement->getAttribute('data-script-blocking-disabled');
            if(!empty($scriptBlockingTag) && $scriptBlockingTag == "true"){
                return $content; //Return the same content, because the script is enabled by data tag
            }
            $current_host = $_SERVER['HTTP_HOST'];
            if($iframe_host !== $current_host){
                $div = $dom->createDocumentFragment();
                $div->appendXML($this->getTemplateHtml(["host"=>$iframe_host,"src"=>$domElement->getAttribute("src")]));

                $regex = '/<iframe[^>]*src=["\']' . preg_quote($domElement->getAttribute("src"), '/') . '["\'][^>]*><\/iframe>/i';
                // Replace the current iframe with the replacement string
                $content = preg_replace($regex, $dom->saveHtml($div), $content);
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
    public function cfHook($content, $extensionConfiguration) : string
    {
        if(!empty($extensionConfiguration["scriptReplaceByRegex"])){
            //Experimental way
            $newContent = $this->replaceIframes($content,"",$extensionConfiguration);
            $newContent = $this->replaceScript($newContent,"",$extensionConfiguration);
        }else{
            //legacy way
            $newContent = $this->overrideIframes($content,"",$extensionConfiguration);
            $newContent = $this->overrideScript($newContent,"",$extensionConfiguration);
        }
        return $newContent;
    }



    /**
     *  Experimental Function to replace Iframes with Divs
     *  The issue is/was that every HTML parser alters the HTML in a way that doesn't match the original. Sometimes the doctype is missing, sometimes closing tags are added that shouldn't be there, and SVG also causes problems, or attributes are completed.
     *  I'm already considering approaching the entire thing differently by not saving the DOM anymore. Instead, I would temporarily read the real DOM to find elements more easily, and replace the HTML directly in the real DOM by using regex.
     */
    public function replaceIframes($content, $database, $extensionConfiguration) : string
    {

      //  $content = '<!DOCTYPE html> <html lang="en"> <head> <meta charset="utf-8"> <!-- Based on the TYPO3 Bootstrap Package by Benjamin Kott - https://www.bootstrap-package.com/ This website is powered by TYPO3 - inspiring people to share! TYPO3 is a free open source Content Management Framework initially created by Kasper Skaarhoj and licensed under GNU/GPL. TYPO3 is copyright 1998-2024 of Kasper Skaarhoj. Extensions are copyright of their respective owners. Information and contribution at https://typo3.org/ --> <link rel="icon" href="/_assets/9b80d86a98af3ecc38aabe297d2c3695/Icons/favicon.ico" type="image/vnd.microsoft.icon"> <title>Iframe Manager</title> <meta http-equiv="x-ua-compatible" content="IE=edge"/> <meta name="generator" content="TYPO3 CMS"/> <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1"/> <meta name="robots" content="index,follow"/> <meta name="twitter:card" content="summary"/> <meta name="apple-mobile-web-app-capable" content="no"/> <meta name="google" content="notranslate"/> <link rel="stylesheet" href="/typo3temp/assets/bootstrappackage/fonts/284ba9c5345a729d38fc3d3bb72eea6caaef6180abbc77928e15e42297d05f8b/webfont.css?1706345756" media="all"> <link rel="stylesheet" href="/typo3temp/assets/compressed/merged-62204b987bd16528a93356eba631f301-min.css?1706349121" media="all"> <link rel="stylesheet" href="/typo3temp/assets/compressed/merged-3397aeebeabf80c5a4a1884885c3a86a-min.css?1706349121" media="all"> <script src="/typo3temp/assets/compressed/merged-44a5e1612857a7bde6bcbfabbed2c0d2-min.js?1706349121" data-service="tripadvisor"></script> <script src="/typo3temp/assets/compressed/iframemanager-min.js?1706349121" defer="defer" data-script-blocking-disabled="true"></script> <script src="/typo3temp/assets/compressed/consent-min.js?1706349121" defer="defer" data-script-blocking-disabled="true"></script> <!-- <script defer="defer" src="https://cookieapi.ddev.site/cdn/consent/cf-cookie-231b62-6d1674-594736-456c9b-f9d99b.js"></script>> --> <link rel="canonical" href="https://fulldemocookiemanager.ddev.site/iframe-manager"/> </head> <body id="p2" class="page-2 pagelevel-1 language-0 backendlayout-default layout-default"> <div id="top"></div> <div class="body-bg"> <a class="visually-hidden-focusable page-skip-link" href="#page-content"> <span>Skip to main content</span> </a> <a class="visually-hidden-focusable page-skip-link" href="#page-footer"> <span>Skip to page footer</span> </a> <header id="page-header" class="bp-page-header navbar navbar-mainnavigation navbar-default navbar-has-image navbar-top"> <div class="container container-mainnavigation"> <a class="navbar-brand navbar-brand-image" href="/"> <img class="navbar-brand-logo-normal" src="/_assets/9b80d86a98af3ecc38aabe297d2c3695/Images/BootstrapPackage.svg" alt="fulldemocookiemanager logo" height="52" width="180"> <img class="navbar-brand-logo-inverted" src="/_assets/9b80d86a98af3ecc38aabe297d2c3695/Images/BootstrapPackageInverted.svg" alt="fulldemocookiemanager logo" height="52" width="180"> </a> <button class="navbar-toggler collapsed" type="text/plain" data-bs-toggle="collapse" data-bs-target="#mainnavigation" aria-controls="mainnavigation" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button> <nav aria-label="Main navigation" id="mainnavigation" class="collapse navbar-collapse"> <ul class="navbar-nav"> <li class="nav-item"> <a id="nav-item-2" href="/iframe-manager" class="nav-link nav-link-main active" aria-current="true" > <span class="nav-link-text"> Iframe Manager <span class="visually-hidden">(current)</span> </span> </a> <div class="dropdown-menu"> </div> </li> <li class="nav-item"> <a id="nav-item-4" href="/script-manager" class="nav-link nav-link-main" aria-current="false" > <span class="nav-link-text"> Script Manager </span> </a> <div class="dropdown-menu"> </div> </li> </ul> </nav> </div> </header> <main id="page-content" class="bp-page-content main-section"> <!--TYPO3SEARCH_begin--> <div class="section section-default"> <div id="c6" class=" frame frame-default frame-type-html frame-layout-default frame-size-default frame-height-default frame-background-none frame-space-before-none frame-space-after-none frame-no-backgroundimage"> <div class="frame-group-container"> <div class="frame-group-inner"> <div class="frame-container frame-container-default"> <div class="frame-inner"> <iframe src="https://player.vimeo.com/video/193020509?h=1cffdfcee1" width="640" height="252" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen></iframe> <p><a href="https://vimeo.com/193020509">ROME: THE ETERNAL CITY. 4K MOTION TIMELAPSE.</a> from <a href="https://vimeo.com/ak806">Alexandr &quot;Sasha&quot; Kravtsov</a> on <a href="https://vimeo.com">Vimeo</a>.</p> </div> </div> </div> </div> </div> <div id="c8" class=" frame frame-default frame-type-html frame-layout-default frame-size-default frame-height-default frame-background-none frame-space-before-none frame-space-after-none frame-no-backgroundimage"> <div class="frame-group-container"> <div class="frame-group-inner"> <div class="frame-container frame-container-default"> <div class="frame-inner"> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://grimming.panomax.com"></iframe> </div> </div> </div> </div> </div> <div id="c9" class=" frame frame-default frame-type-html frame-layout-default frame-size-default frame-height-default frame-background-none frame-space-before-none frame-space-after-none frame-no-backgroundimage"> <div class="frame-group-container"> <div class="frame-group-inner"> <div class="frame-container frame-container-default"> <div class="frame-inner"> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://podcasters.spotify.com/pod/show/how-to-wien/embed/episodes/Folge-1150---Mah-Grtzl-eotnjq"></iframe> </div> </div> </div> </div> </div> <div id="c10" class=" frame frame-default frame-type-html frame-layout-default frame-size-default frame-height-default frame-background-none frame-space-before-none frame-space-after-none frame-no-backgroundimage"> <div class="frame-group-container"> <div class="frame-group-inner"> <div class="frame-container frame-container-default"> <div class="frame-inner"> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://sketchfab.com/models/2f51a3638e3b483eaea98f0aa34cce1b/embed"></iframe> <div class="frame-inner"><h2> tripadvisor </h2><div id="TA_rated23" class="TA_rated"><ul id="YiKFwt9G" class="TA_links lTz7Pe"><li id="6K9mN75AtI" class="Op4ZHh1"><a target="_blank" href="https://www.tripadvisor.de/Attraction_Review-g190432-d1739833-Reviews-Universalmuseum_Joanneum-Graz_Styria.html"><img src="https://www.tripadvisor.de/img/cdsi/img2/badges/ollie-11424-2.gif" alt="TripAdvisor"></a></li></ul></div><script async="true" src="https://www.jscache.com/wejs?wtype=rated&amp;uniq=23&amp;locationId=1739833&amp;lang=de&amp;display_version=2" data-loadtrk="" onload="function onload(event) { this.loadtrk=true }"></script></div> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://w.soundcloud.com/player/?url=https%3A//api.soundcloud.com/tracks/1411034371&amp;amp;color=%23ff5500&amp;amp;auto_play=false&amp;amp;hide_related=false&amp;amp;show_comments=true&amp;amp;show_user=true&amp;amp;show_reposts=false&amp;amp;show_teaser=true&amp;amp;visual=true"></iframe> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://my.matterport.com/show/?m=Srdq49wjRh4"></iframe> <iframe allow=" accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen; " src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d823.6327959948685!2d16.36413070482941!3d48.2113044564088!2m3!1f126.24017515469238!2f44.737459229643896!3f0!3m2!1i1024!2i768!4f35!5e1!3m2!1sde!2sat!4v1676315877608!5m2!1sde!2sat"></iframe> </div> </div> </div> </div> </div> </div> <!--TYPO3SEARCH_end--> </main> <footer id="page-footer" class="bp-page-footer"> <section class="section footer-section footer-section-content"> <div class="container"> <div class="section-row"> <div class="section-column footer-section-content-column footer-section-content-column-left"> </div> <div class="section-column footer-section-content-column footer-section-content-column-middle"> </div> <div class="section-column footer-section-content-column footer-section-content-column-right"> </div> </div> </div> </section> <section class="section footer-section footer-section-meta"> <div class="frame frame-background-none frame-space-before-none frame-space-after-none"> <div class="frame-group-container"> <div class="frame-group-inner"> <div class="frame-container frame-container-default"> <div class="frame-inner"> <div class="footer-info-section"> <div class="footer-meta"> </div> <div class="footer-language"> <ul id="language_menu" class="language-menu"> <li class="active "> <a href="/iframe-manager" hreflang="en-US" title="English"> <span>English</span> </a> </li> </ul> </div> <div class="footer-copyright"> <p>Running with <a href="http://www.typo3.org" target="_blank" rel="noreferrer noopener">TYPO3</a> and <a href="https://www.bootstrap-package.com" target="_blank" rel="noreferrer noopener">Bootstrap Package</a>.</p> </div> <div class="footer-sociallinks"> <div class="sociallinks"> <ul class="sociallinks-list"> </ul> </div> </div> </div> </div> </div> </div> </div> </div> </section> </footer> <a class="scroll-top" title="Scroll to top" href="#top"> <span class="scroll-top-icon"></span> </a> </div> <div class="tx-cf-cookiemanager"> <div style="display: none;" id="cf-cookiemanager-1682010733" data-page="" data-url="aHR0cHM6Ly9mdWxsZGVtb2Nvb2tpZW1hbmFnZXIuZGRldi5zaXRlL2lmcmFtZS1tYW5hZ2VyP3R4X2NmY29va2llbWFuYWdlcl9jb29raWVmcm9udGVuZCU1QmFjdGlvbiU1RD10cmFjayZ0eF9jZmNvb2tpZW1hbmFnZXJfY29va2llZnJvbnRlbmQlNUJjb250cm9sbGVyJTVEPUNvb2tpZUZyb250ZW5kJnR5cGU9MTY4MjAxMDczMyZjSGFzaD1kYmRiZGMyMjBlM2QyN2Q1MzBhMmY1MGI1NzQ5OTE2ZA=="></div> <div class="cf-cookie-openconsent"> <a type="button" data-cc="c-settings" href="#" title="cookie consent" aria-haspopup="dialog"> <img title="Cookie Settings" alt="Cookie Extension Logo" src="/_assets/0495dc5aa206d96a6c2bfbe3dbb13f6d/Icons/Extension.svg" width="30" height="32"/> </a> </div> </div> <script src="/typo3temp/assets/compressed/merged-312dfdb2fc2ad0e2faf85ba6a65f5ea7-min.js?1706349121"></script> <script defer="defer" data-script-blocking-disabled="true" src="/typo3temp/assets/compressed/cookieconfig035dba5d75538a9bbe0b4da4422759a0e-min.js?1706349121"></script> </body> </html>';
        //return $content;
        if(!$this->isHTML($content)){
            return $content;
        }

        $html5 = new HTML5(['disable_html_ns' => true]);
        $dom = $html5->loadHTML($content);
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
                    $content = $this->scriptBlockerRegex($iframe,$dom,$content);

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

                //parse url and get host name
                $completeUrlWithoutParameters = parse_url($attributes["src"], PHP_URL_HOST) . parse_url($attributes["src"], PHP_URL_PATH);
                //$iframeRegexPattern = '/<iframe[^>]*src=["\']' . preg_quote($attributes["src"], '/') . '["\'][^>]*><\/iframe>/i';
                $iframeRegexPattern = '/<iframe[^>]*?(?:\/>|>[^<]*?<\/iframe>)/i';
                preg_match_all($iframeRegexPattern, $content, $matches);
                if(empty($matches)){
                    //No Match found, this script is found, but can not be replaced by regex, trigger warning and continue
                    continue;
                }
                foreach ($matches[0] as $originalIframeTag) {
                    if(strpos($originalIframeTag, $completeUrlWithoutParameters) !== false){
                        // Replace the original script tag with the modified script tag in the content
                        $content = str_replace($originalIframeTag, $dom->saveHTML($div), $content);
                    }
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
            $htmlStr = preg_replace("~(<$tagname\s.*?$attributeName=)([\'\"])(.*?)([\'\"])~i", '$1$2' . $attributeValue . '$4', $htmlStr,1);
        }
        return $htmlStr;
    }


    public function replaceScript($content, $database, $extensionConfiguration) : string
    {
        if(!$this->isHTML($content)){
            return $content;
        }

        $html5 = new HTML5(['disable_html_ns' => true]);
        $dom = $html5->loadHTML($content);
        $xpath = new \DOMXPath($dom);
        $scripts = $xpath->query('//script');

        foreach ($scripts as $script) {
            $attributes = array();
            foreach ($script->attributes as $attr) {
                $attributes[$attr->name] = $attr->value;
            }

            if(empty($attributes["src"])) {
                continue;
            }

            $serviceIdentifier = $this->classifyContent($attributes["src"]);

            $urlEmbeded = $attributes["src"];
            // Parse the URL to ignore the GET parameters
            if(!empty(parse_url($attributes["src"], PHP_URL_HOST) )){
                    $urlEmbeded = parse_url($attributes["src"], PHP_URL_HOST) . parse_url($attributes["src"], PHP_URL_PATH);
            }

            //$completeUrlWithoutParameters = '/typo3temp/assets/compressed/merged-44a5e1612857a7bde6bcbfabbed2c0d2-min.js?1706349121'; // replace with your actual URL
            $scriptRegexPattern = '/<script[^>]*?(?:\/>|>[^<]*?<\/script>)/im';

            // Get the "original" script tag from the DOM parser, not 100% SAVE!
            //$originalScriptTag = $dom->saveHTML($script);
            // Get the original script tag from the content String by Regex
            preg_match_all($scriptRegexPattern, $content, $matches);
            if(empty($matches)){
                //No Match found, this script is found, but can not be replaced by regex, trigger warning and continue
                continue;
            }


            if(empty($serviceIdentifier)){
                if(intval($extensionConfiguration["scriptBlocking"]) === 1){
                    foreach ($matches[0] as $originalScriptTag) {
                        //Should we use Templates here? or just remove the script tag?
                        $content = str_replace($originalScriptTag, '', $content);
                    }
                }
            } else {
                foreach ($matches[0] as $originalScriptTag) {
                    //DebuggerUtility::var_dump($match);
                    if(strpos($originalScriptTag, $urlEmbeded) !== false){
                        //Script is not replaced, replace it
                        $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($originalScriptTag, 'script', 'type', 'text/plain');
                        $modifiedScriptTag = $this->addHtmlAttribute_in_HTML_Tag($modifiedScriptTag, 'script', 'data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));

                        // Replace the original script tag with the modified script tag in the content
                        $content = str_replace($originalScriptTag, $modifiedScriptTag, $content);
                    }
                }
            }
        }
        return $content;
    }
}
