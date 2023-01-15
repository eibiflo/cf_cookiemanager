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
        if (!preg_match("~<$tagname\s.*?$attributeName=([\'\"])~i", $htmlStr)) {
            // if html tag attribute does not exist then add it ...
            $htmlStr = preg_replace('/(<' . $tagname . '\b[^><]*)>/i', '$1 ' . $attributeName . '="' . $attributeValue . '">', $htmlStr, 1);
        }
        return $htmlStr;
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
        //DebuggerUtility::var_dump($content);
        $serviceIdentifier = $this->classifyContent($content, $databaseRow);
        if (!empty($serviceIdentifier)) {
            //$newContentTmp =  $this->addHtmlAttribute_in_HTML_Tag($content,"div","data-category",$category);
            $newContent = $this->addHtmlAttribute_in_HTML_Tag($content, "div", "data-cookiecategory", $serviceIdentifier);
            return $this->overrideIframe($newContent);
        }

        return $content;
    }
}