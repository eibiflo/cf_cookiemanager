<?php


namespace  CodingFreaks\CfCookiemanager\Utility;

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
     * Classify Content in a HTML string
     *
     * @param string $content
     * @param string $databaseRow
     * @return string
     */
    public function classifyContent($content, $dbRow): string
    {
        preg_match("/<iframe.*src=\"(.*)\".*><\/iframe>/", $content, $detectedIframes);
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
                foreach ($providers as $provider) {
                    if (str_contains($iframeURL, $provider)) {
                        //Content Blocker Found a Match
                        //IF FORCE BLOCK RETURN NOW.
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
        if (!empty($serviceIdentifier)) {
            //$newContentTmp =  $this->addHtmlAttribute_in_HTML_Tag($content,"div","data-category",$category);
            $newContent = $this->addHtmlAttribute_in_HTML_Tag($content, "div", "data-cookiecategory", $serviceIdentifier);
            return $this->overrideIframe($newContent,$serviceIdentifier);
        }

        return $content;
    }
}