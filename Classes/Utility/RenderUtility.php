<?php


namespace  CodingFreaks\CfCookiemanager\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class RenderUtility
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
        /** if html tag attribute does not exist then add it ... */
        if (!preg_match("~<$tagname\s.*?$attributeName=([\'\"])~i", $htmlStr)) {
            $htmlStr = preg_replace('/(<' . $tagname . '\b[^><]*)>/i', '$1 ' . $attributeName . '="' . $attributeValue . '">', $htmlStr, 1);
        }
        return $htmlStr;
    }

    /**
     * Find and replace a script tag and override the attribute to text/plain
     *
     * @param string $html
     * @param string $serviceIdentifier
     * @return string
     */
    public function overrideScript($html, $serviceIdentifier): string
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new \DOMXPath($doc);
        $scripts = $xpath->query('//script');
        foreach ($scripts as $script) {
            $scriptTag = $doc->createElement('script');
            $scriptTag->setAttribute('data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));
            $scriptTag->setAttribute('type', "text/plain");
            // Replace iframe element with new div element
            foreach ($script->attributes as $attr) {
                // Validate and sanitize attribute values
                $scriptTag->setAttribute($attr->name, $attr->value);
            }

            $script->parentNode->replaceChild($scriptTag, $script);
        }

        return $doc->saveHTML();
    }

    /**
     * Find and replace a iframe and override it with a Div to Inject iFrameManager in Frontend
     *
     * @param string $html
     * @param string $serviceIdentifier
     * @return string
     */
    public function overrideIframe($html, $serviceIdentifier): string
    {
        $doc = new \DOMDocument();
        $doc->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new \DOMXPath($doc);
        $iframes = $xpath->query('//iframe');

        foreach ($iframes as $iframe) {
            $attributes = array();
            foreach ($iframe->attributes as $attr) {
                // Validate and sanitize attribute values
                $attrValue = htmlentities($attr->value, ENT_QUOTES, 'UTF-8');
                $attributes[$attr->name] = $attrValue;
            }

            $inlineStyle = '';
            if (isset($attributes["height"])) {
                $inlineStyle .= strpos($attributes["height"], 'px') !== false ? "height:{$attributes["height"]}; " : "height:{$attributes["height"]}px; ";
            }
            if (isset($attributes["width"])) {
                $inlineStyle .= strpos($attributes["width"], 'px') !== false ? "width:{$attributes["width"]}; " : "width:{$attributes["width"]}px; ";
            }
            $inlineStyle = isset($attributes["style"]) ? htmlentities($attributes["style"], ENT_QUOTES, 'UTF-8') . $inlineStyle : $inlineStyle;

            // Create new div element with sanitized attributes
            $div = $doc->createElement('div');
            $div->setAttribute('style', $inlineStyle);
            $div->setAttribute('data-service', htmlentities($serviceIdentifier, ENT_QUOTES, 'UTF-8'));
            $div->setAttribute('data-id', $attributes["src"]);
            $div->setAttribute('data-autoscale', "");

            // Replace iframe element with new div element
            $iframe->parentNode->replaceChild($div, $iframe);
        }

        return $doc->saveHTML();
    }

    /**
     * Classify Content by Searching for Iframes and Scripts get URLs and find the Service, if not Found Return false
     *
     * @param string $content
     * @param string $databaseRow
     * @return string
     */
    public function classifyContent($content, $dbRow): string
    {
        preg_match("/<iframe.*src=\"([^\"]*)\"[^>]*>.*<\/iframe>/", $content, $detectedIframes);
        preg_match_all("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i", $content, $detectedScripts);

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
        $result = $queryBuilder->execute()->fetchAll();


        foreach ($result as $service) {
            if (!empty($service["provider"])) {
                $providers = explode(",", $service["provider"]);
                $iframeURL = $detectedIframes[1];
                $scriptContent = implode("", $detectedScripts[0]);

                foreach ($providers as $provider) {
                    if (str_contains($iframeURL, $provider) || str_contains($scriptContent, $provider)) {
                        //Content Blocker Found a Match
                        //IF FORCE BLOCK RETURN NOW.
                        return $service["identifier"];
                    }
                }
            }
        }


        return false;
    }


    public function getHtmlAttributes($htmlString,$tagName = "script"){
        $dom = new \DOMDocument();
        $dom->loadHTML($htmlString);
        $nodes = $dom->getElementsByTagName($tagName);

        $attributes = [];
        foreach ($nodes as $node)
        {
            if(empty($node->attributes)){
               continue;
            }
            foreach ($node->attributes as $attr )
            {
                $attributes[$attr->name] = $attr->value;
            }
        }

        return $attributes;
    }

    /**
     * Prevents the loading of content such as iframes and scripts from third-party sources, can be Disabled by adding a Data Atribute to the Script or Iframe (data-script-blocking-disabled="true")
     *
     * @param string $content The HTML content to be checked.
     * @return string The "modified" HTML content or an error message if the content was blocked.
     */
    public function scriptBlocker($content){
        preg_match_all("/<iframe.*src=\"(.*)\".*><\/iframe>/", $content, $detectedIframes);
        preg_match_all("/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/i", $content, $detectedScripts);

        if(!empty($detectedIframes[1])){
            foreach($detectedIframes[1] as $iframe){
                $iframe_host = parse_url($iframe, PHP_URL_HOST);
                $current_host = $_SERVER['HTTP_HOST'];
                if($iframe_host !== $current_host){
                    return "Blocked by Content-Blocker. Please contact your Site Administrator.";
                }
            }
        }


        foreach ($detectedScripts[0] as $script){
            $attributes = $this->getHtmlAttributes($script,"script");
            if(!empty($attributes["data-script-blocking-disabled"])){
                continue;
            }
            if(!empty($attributes["src"])){
                $script_host = parse_url($attributes["src"], PHP_URL_HOST);
                $current_host = $_SERVER['HTTP_HOST'];
                if($script_host !== $current_host){
                    return "Blocked by Content-Blocker. Please contact your Site Administrator.";
                }
            }
        }

        return $content;
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
        $serviceIdentifier = $this->classifyContent($content, $databaseRow);

        if (!empty($serviceIdentifier)) {
            //$newContentTmp =  $this->addHtmlAttribute_in_HTML_Tag($content,"div","data-category",$category);
            $newContent = $this->addHtmlAttribute_in_HTML_Tag($content, "div", "data-cookiecategory", $serviceIdentifier);
            $newContent = $this->overrideIframe($newContent,$serviceIdentifier);
            $newContent = $this->overrideScript($newContent,$serviceIdentifier);
            return $newContent;
        }

        if(intval($extensionConfiguration["scriptBlocking"]) === 1){
            //Script Blocking is enabled so Block all Scripts and Iframes
            $content = $this->scriptBlocker($content);
        }

        return $serviceIdentifier.$content;
    }
}