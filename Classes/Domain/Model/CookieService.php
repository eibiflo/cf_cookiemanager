<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Model;


use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 
 */

/**
 * CookieService
 */
class CookieService extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * hidden
     *
     * @var bool
     */
    protected $hidden = 0;

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * description
     *
     * @var string
     */
    protected $description = '';

    /**
     * isRequired
     *
     * @var int
     */
    protected $isRequired = 0;

    /**
     * isReadonly
     *
     * @var int
     */
    protected $isReadonly = 0;

    /**
     * provider
     *
     * @var string
     */
    protected $provider = '';

    /**
     * optInCode
     *
     * @var string
     */
    protected $optInCode = '';

    /**
     * optOutCode
     *
     * @var string
     */
    protected $optOutCode = '';

    /**
     * fallbackCode
     *
     * @var string
     */
    protected $fallbackCode = '';

    /**
     * dsgvoLink
     *
     * @var string
     */
    protected $dsgvoLink = '';

    /**
     * iframeEmbedUrl
     *
     * @var string
     */
    protected $iframeEmbedUrl = '';

    /**
     * iframeThumbnailUrl
     *
     * @var string
     */
    protected $iframeThumbnailUrl = '';

    /**
     * iframeNotice
     *
     * @var string
     */
    protected $iframeNotice = '';

    /**
     * iframeLoadBtn
     *
     * @var string
     */
    protected $iframeLoadBtn = '';

    /**
     * iframeLoadAllBtn
     *
     * @var string
     */
    protected $iframeLoadAllBtn = '';

    /**
     * categorySuggestion
     *
     * @var string
     */
    protected $categorySuggestion = '';

    /**
     * cookie
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Cookie>
     */
    protected $cookie = null;


    /**
     * externalScripts
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $externalScripts = null;

    /**
     * variablePriovider
     *
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Variables>
     * @TYPO3\CMS\Extbase\Annotation\ORM\Cascade("remove")
     */
    protected $variablePriovider = null;

    /**
     * excludeFromUpdate
     *
     * @var bool
     */
    protected $excludeFromUpdate = false;

    /**
     * Returns the name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets the name
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Returns the identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Sets the identifier
     *
     * @param string $identifier
     * @return void
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Returns the description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the description
     *
     * @param string $description
     * @return void
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * Returns the isRequired
     *
     * @return bool isRequired
     */
    public function getIsRequired()
    {
        return $this->isRequired;
    }

    /**
     * Sets the isRequired
     *
     * @param int $isRequired
     * @return void
     */
    public function setIsRequired(int $isRequired)
    {
        $this->isRequired = $isRequired;
    }

    /**
     * Returns the isReadonly
     *
     * @return bool isReadonly
     */
    public function getIsReadonly()
    {
        return $this->isReadonly;
    }

    /**
     * Sets the isReadonly
     *
     * @param int $isReadonly
     * @return void
     */
    public function setIsReadonly(int $isReadonly)
    {
        $this->isReadonly = $isReadonly;
    }

    /**
     * Returns the provider
     *
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Sets the provider
     *
     * @param string $provider
     * @return void
     */
    public function setProvider(string $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Returns the optInCode
     *
     * @return string
     */
    public function getOptInCode()
    {
        return $this->optInCode;
    }

    /**
     * Sets the optInCode
     *
     * @param string $optInCode
     * @return void
     */
    public function setOptInCode(string $optInCode)
    {
        $this->optInCode = $optInCode;
    }

    /**
     * Returns the optOutCode
     *
     * @return string
     */
    public function getOptOutCode()
    {
        return $this->optOutCode;
    }

    /**
     * Sets the optOutCode
     *
     * @param string $optOutCode
     * @return void
     */
    public function setOptOutCode(string $optOutCode)
    {
        $this->optOutCode = $optOutCode;
    }

    /**
     * Returns the fallbackCode
     *
     * @return string
     */
    public function getFallbackCode()
    {
        return $this->fallbackCode;
    }

    /**
     * Sets the fallbackCode
     *
     * @param string $fallbackCode
     * @return void
     */
    public function setFallbackCode(string $fallbackCode)
    {
        $this->fallbackCode = $fallbackCode;
    }

    /**
     * Returns the dsgvoLink
     *
     * @return string
     */
    public function getDsgvoLink()
    {
        return $this->dsgvoLink;
    }

    /**
     * Sets the dsgvoLink
     *
     * @param string $dsgvoLink
     * @return void
     */
    public function setDsgvoLink(string $dsgvoLink)
    {
        $this->dsgvoLink = $dsgvoLink;
    }

    /**
     * __construct
     */
    public function __construct()
    {

        // Do not remove the next line: It would break the functionality
        $this->initializeObject();
    }

    /**
     * Initializes all ObjectStorage properties when model is reconstructed from DB (where __construct is not called)
     * Do not modify this method!
     * It will be rewritten on each save in the extension builder
     * You may modify the constructor of this class instead
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->cookie = $this->cookie ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->externalScripts = $this->externalScripts ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
        $this->variablePriovider = $this->variablePriovider ?: new \TYPO3\CMS\Extbase\Persistence\ObjectStorage();
    }

    /**
     * Adds a ExternalScripts
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts $externalScript
     * @return void
     */
    public function addExternalScript(\CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts $externalScript)
    {
        $this->externalScripts->attach($externalScript);
    }

    /**
     * Removes a ExternalScripts
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts $externalScriptToRemove The ExternalScripts to be removed
     * @return void
     */
    public function removeExternalScript(\CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts $externalScriptToRemove)
    {
        $this->externalScripts->detach($externalScriptToRemove);
    }

    /**
     * Returns the externalScripts
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts>
     */
    public function getExternalScripts()
    {
        return $this->externalScripts;
    }

    /**
     * Sets the externalScripts
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\ExternalScripts> $externalScripts
     * @return void
     */
    public function setExternalScripts(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $externalScripts)
    {
        $this->externalScripts = $externalScripts;
    }

    /**
     * Adds a Variables
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\Variables $variablePriovider
     * @return void
     */
    public function addVariablePriovider(\CodingFreaks\CfCookiemanager\Domain\Model\Variables $variablePriovider)
    {
        $this->variablePriovider->attach($variablePriovider);
    }

    /**
     * Removes a Variables
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\Variables $variablePrioviderToRemove The Variables to be removed
     * @return void
     */
    public function removeVariablePriovider(\CodingFreaks\CfCookiemanager\Domain\Model\Variables $variablePrioviderToRemove)
    {
        $this->variablePriovider->detach($variablePrioviderToRemove);
    }

    /**
     * Returns the variablePriovider
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Variables>
     */
    public function getVariablePriovider()
    {
        return $this->variablePriovider;
    }

    /**
     * Sets the variablePriovider
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Variables> $variablePriovider
     * @return void
     */
    public function setVariablePriovider(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $variablePriovider)
    {
        $this->variablePriovider = $variablePriovider;
    }

    /**
     * Adds a Cookie
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\Cookie $cookie
     * @return void
     */
    public function addCookie(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie $cookie)
    {
        $this->cookie->attach($cookie);
    }

    /**
     * Removes a Cookie
     *
     * @param \CodingFreaks\CfCookiemanager\Domain\Model\Cookie $cookieToRemove The Cookie to be removed
     * @return void
     */
    public function removeCookie(\CodingFreaks\CfCookiemanager\Domain\Model\Cookie $cookieToRemove)
    {
        $this->cookie->detach($cookieToRemove);
    }

    /**
     * Returns the cookie
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Cookie>
     */
    public function getCookie()
    {
        return $this->cookie;
    }

    /**
     * Sets the cookie
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\CodingFreaks\CfCookiemanager\Domain\Model\Cookie> $cookie
     * @return void
     */
    public function setCookie(\TYPO3\CMS\Extbase\Persistence\ObjectStorage $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Returns the iframeEmbedUrl
     *
     * @return string
     */
    public function getIframeEmbedUrl()
    {
        return $this->iframeEmbedUrl;
    }

    /**
     * Sets the iframeEmbedUrl
     *
     * @param string $iframeEmbedUrl
     * @return void
     */
    public function setIframeEmbedUrl(string $iframeEmbedUrl)
    {
        $this->iframeEmbedUrl = $iframeEmbedUrl;
    }

    /**
     * Returns the iframeThumbnailUrl
     *
     * @return string
     */
    public function getIframeThumbnailUrl()
    {
        return $this->iframeThumbnailUrl;
    }

    /**
     * Sets the iframeThumbnailUrl
     *
     * @param string $iframeThumbnailUrl
     * @return void
     */
    public function setIframeThumbnailUrl(string $iframeThumbnailUrl)
    {
        $this->iframeThumbnailUrl = $iframeThumbnailUrl;
    }

    /**
     * Returns the iframeNotice
     *
     * @return string
     */
    public function getIframeNotice()
    {
        return $this->iframeNotice;
    }

    /**
     * Sets the iframeNotice
     *
     * @param string $iframeNotice
     * @return void
     */
    public function setIframeNotice(string $iframeNotice)
    {
        $this->iframeNotice = $iframeNotice;
    }

    /**
     * Returns the iframeLoadBtn
     *
     * @return string
     */
    public function getIframeLoadBtn()
    {
        return $this->iframeLoadBtn;
    }

    /**
     * Sets the iframeLoadBtn
     *
     * @param string $iframeLoadBtn
     * @return void
     */
    public function setIframeLoadBtn(string $iframeLoadBtn)
    {
        $this->iframeLoadBtn = $iframeLoadBtn;
    }

    /**
     * Returns the iframeLoadAllBtn
     *
     * @return string
     */
    public function getIframeLoadAllBtn()
    {
        return $this->iframeLoadAllBtn;
    }

    /**
     * Sets the iframeLoadAllBtn
     *
     * @param string $iframeLoadAllBtn
     * @return void
     */
    public function setIframeLoadAllBtn(string $iframeLoadAllBtn)
    {
        $this->iframeLoadAllBtn = $iframeLoadAllBtn;
    }

    /**
     * Returns the categorySuggestion
     *
     * @return string
     */
    public function getCategorySuggestion()
    {
        return $this->categorySuggestion;
    }

    /**
     * Sets the categorySuggestion
     *
     * @param string $categorySuggestion
     * @return void
     */
    public function setCategorySuggestion(string $categorySuggestion)
    {
        $this->categorySuggestion = $categorySuggestion;
    }

    /**
     * Returns the hidden field
     *
     * @return bool hidden
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Sets the hidden field
     *
     * @param bool $bool
     * @return void
     */
    public function setHidden(bool $bool)
    {
        $this->hidden = $bool;
    }

    public function getUsedVariables(){
        $code = $this->getOptInCode();
        $code .= $this->getOptOutCode();
        $code .= $this->getFallbackCode();
        $pattern = '/\[##(.*?)##\]/'; // Regex-Muster, um Zeichenketten zwischen [## und ##] zu finden
        $matches = array();
        preg_match_all($pattern, $code, $matches);
        return $matches[1];
    }

    public function getUnknownVariables(){
        $variables = $this->getUsedVariables();
        $variablesAssigned = $this->getVariablePriovider();
        $foundVariablesCount = 0;
        $foundVariables = [];

        foreach ($variables as $usedVariable){
            foreach ($variablesAssigned as $tempVal){
                //found
                if($usedVariable == $tempVal->getIdentifier()){
                    if(!empty($tempVal->getIdentifier())){
                        $foundVariablesCount++;
                        $foundVariables[] = $tempVal->getIdentifier();
                    }
                }
            }
        }

        if($foundVariablesCount == count($variables)){
            //All Variables are assigned
            return true;
        }

        return array_diff($variables,$foundVariables);
    }

    /**
     * Returns the excludeFromUpdate
     *
     * @return bool
     */
    public function getExcludeFromUpdate(): bool
    {
        return $this->excludeFromUpdate;
    }

    /**
     * Sets the excludeFromUpdate
     *
     * @param bool $excludeFromUpdate
     * @return void
     */
    public function setExcludeFromUpdate(bool $excludeFromUpdate): void
    {
        $this->excludeFromUpdate = $excludeFromUpdate;
    }
}
