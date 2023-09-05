<?php

declare(strict_types=1);

namespace CodingFreaks\CfCookiemanager\Domain\Model;


/**
 * This file is part of the "Coding Freaks Cookie Manager" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) 2022 Florian Eibisberger, CodingFreaks
 */

/**
 * CookieFrontend
 */
class CookieFrontend extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{

    /**
     * hidden
     *
     * @var bool
     */
    protected $hidden = 0;


    /**
     * revisionText
     *
     * @var string
     */
    protected $revisionText = '';

    /**
     * identifier
     *
     * @var string
     */
    protected $identifier = '';

    /**
     * name
     *
     * @var string
     */
    protected $name = '';

    /**
     * enabled
     *
     * @var bool
     */
    protected $enabled = false;

    /**
     * titleConsentModal
     *
     * @var string
     */
    protected $titleConsentModal = '';

    /**
     * descriptionConsentModal
     *
     * @var string
     */
    protected $descriptionConsentModal = '';

    /**
     * primaryBtnTextConsentModal
     *
     * @var string
     */
    protected $primaryBtnTextConsentModal = '';

    /**
     * primaryBtnRoleConsentModal
     *
     * @var string
     */
    protected $primaryBtnRoleConsentModal = 'accept_all';

    /**
     * secondaryBtnTextConsentModal
     *
     * @var string
     */
    protected $secondaryBtnTextConsentModal = '';

    /**
     * secondaryBtnRoleConsentModal
     *
     * @var string
     */
    protected $secondaryBtnRoleConsentModal = 'accept_necessary';

    /**
     * tertiaryBtnRoleConsentModal
     *
     * @var string
     */
    protected $tertiaryBtnRoleConsentModal = 'display_none';

    /**
     * tertiaryBtnTextConsentModal
     *
     * @var string
     */
    protected $tertiaryBtnTextConsentModal = '';

    /**
     * titleSettings
     *
     * @var string
     */
    protected $titleSettings = '';

    /**
     * saveBtnSettings
     *
     * @var string
     */
    protected $saveBtnSettings = '';

    /**
     * acceptAllBtnSettings
     *
     * @var string
     */
    protected $acceptAllBtnSettings = '';

    /**
     * rejectAllBtnSettings
     *
     * @var string
     */
    protected $rejectAllBtnSettings = '';

    /**
     * closeBtnSettings
     *
     * @var string
     */
    protected $closeBtnSettings = '';

    /**
     * col1HeaderSettings
     *
     * @var string
     */
    protected $col1HeaderSettings = '';

    /**
     * col2HeaderSettings
     *
     * @var string
     */
    protected $col2HeaderSettings = '';

    /**
     * col3HeaderSettings
     *
     * @var string
     */
    protected $col3HeaderSettings = '';

    /**
     * blocksTitle
     *
     * @var string
     */
    protected $blocksTitle = '';

    /**
     * blocksDescription
     *
     * @var string
     */
    protected $blocksDescription = '';

    /**
     * custombutton
     *
     * @var bool
     */
    protected $custombutton = false;

    /**
     * customButtonHtml
     *
     * @var string
     */
    protected $customButtonHtml = '';

    /**
     * inLineExecution
     *
     * @var bool
     */
    protected $inLineExecution = false;

    /**
     * layoutConsentModal
     *
     * @var string
     */
    protected $layoutConsentModal = "box";

    /**
     * layoutSettings
     *
     * @var string
     */
    protected $layoutSettings = "box";

    /**
     * positionConsentModal
     *
     * @var string
     */
    protected $positionConsentModal = "bottom center";

    /**
     * positionSettings
     *
     * @var string
     */
    protected $positionSettings = "right";

    /**
     * transitionConsentModal
     *
     * @var string
     */
    protected $transitionConsentModal = "slide";

    /**
     * transitionSettings
     *
     * @var string
     */
    protected $transitionSettings = "slide";

    /**
     * impress_link
     *
     * @var string
     */
    protected $impressLink = '';

    /**
     * impress_text
     *
     * @var string
     */
    protected $impressText = '';

    /**
     * data_policy_link
     *
     * @var string
     */
    protected $dataPolicyLink = '';

    /**
     * data_policy_text
     *
     * @var string
     */
    protected $dataPolicyText = '';


    /**
     * Returns the titleConsentModal
     *
     * @return string
     */
    public function getTitleConsentModal()
    {
        return $this->titleConsentModal;
    }

    /**
     * Sets the titleConsentModal
     *
     * @param string $titleConsentModal
     * @return void
     */
    public function setTitleConsentModal(string $titleConsentModal)
    {
        $this->titleConsentModal = $titleConsentModal;
    }

    /**
     * Returns the descriptionConsentModal
     *
     * @return string
     */
    public function getDescriptionConsentModal()
    {
        return $this->descriptionConsentModal;
    }

    /**
     * Sets the descriptionConsentModal
     *
     * @param string $descriptionConsentModal
     * @return void
     */
    public function setDescriptionConsentModal(string $descriptionConsentModal)
    {
        $this->descriptionConsentModal = $descriptionConsentModal;
    }

    /**
     * Returns the primaryBtnTextConsentModal
     *
     * @return string
     */
    public function getPrimaryBtnTextConsentModal()
    {
        return $this->primaryBtnTextConsentModal;
    }

    /**
     * Sets the primaryBtnTextConsentModal
     *
     * @param string $primaryBtnTextConsentModal
     * @return void
     */
    public function setPrimaryBtnTextConsentModal(string $primaryBtnTextConsentModal)
    {
        $this->primaryBtnTextConsentModal = $primaryBtnTextConsentModal;
    }

    /**
     * Returns the secondaryBtnTextConsentModal
     *
     * @return string
     */
    public function getSecondaryBtnTextConsentModal()
    {
        return $this->secondaryBtnTextConsentModal;
    }

    /**
     * Sets the secondaryBtnTextConsentModal
     *
     * @param string $secondaryBtnTextConsentModal
     * @return void
     */
    public function setSecondaryBtnTextConsentModal(string $secondaryBtnTextConsentModal)
    {
        $this->secondaryBtnTextConsentModal = $secondaryBtnTextConsentModal;
    }


    /**
     * Returns the tertiaryBtnTextConsentModal
     *
     * @return string
     */
    public function getTertiaryBtnTextConsentModal()
    {
        return $this->tertiaryBtnTextConsentModal;
    }

    /**
     * Sets the tertiaryBtnTextConsentModal
     *
     * @param string $tertiaryBtnTextConsentModal
     * @return void
     */
    public function setTertiaryBtnTextConsentModal(string $tertiaryBtnTextConsentModal)
    {
        $this->tertiaryBtnTextConsentModal = $tertiaryBtnTextConsentModal;
    }


    /**
     * Returns the titleSettings
     *
     * @return string
     */
    public function getTitleSettings()
    {
        return $this->titleSettings;
    }

    /**
     * Sets the titleSettings
     *
     * @param string $titleSettings
     * @return void
     */
    public function setTitleSettings(string $titleSettings)
    {
        $this->titleSettings = $titleSettings;
    }

    /**
     * Returns the saveBtnSettings
     *
     * @return string
     */
    public function getSaveBtnSettings()
    {
        return $this->saveBtnSettings;
    }

    /**
     * Sets the saveBtnSettings
     *
     * @param string $saveBtnSettings
     * @return void
     */
    public function setSaveBtnSettings(string $saveBtnSettings)
    {
        $this->saveBtnSettings = $saveBtnSettings;
    }

    /**
     * Returns the acceptAllBtnSettings
     *
     * @return string
     */
    public function getAcceptAllBtnSettings()
    {
        return $this->acceptAllBtnSettings;
    }

    /**
     * Sets the acceptAllBtnSettings
     *
     * @param string $acceptAllBtnSettings
     * @return void
     */
    public function setAcceptAllBtnSettings(string $acceptAllBtnSettings)
    {
        $this->acceptAllBtnSettings = $acceptAllBtnSettings;
    }

    /**
     * Returns the rejectAllBtnSettings
     *
     * @return string
     */
    public function getRejectAllBtnSettings()
    {
        return $this->rejectAllBtnSettings;
    }

    /**
     * Sets the rejectAllBtnSettings
     *
     * @param string $rejectAllBtnSettings
     * @return void
     */
    public function setRejectAllBtnSettings(string $rejectAllBtnSettings)
    {
        $this->rejectAllBtnSettings = $rejectAllBtnSettings;
    }

    /**
     * Returns the closeBtnSettings
     *
     * @return string
     */
    public function getCloseBtnSettings()
    {
        return $this->closeBtnSettings;
    }

    /**
     * Sets the closeBtnSettings
     *
     * @param string $closeBtnSettings
     * @return void
     */
    public function setCloseBtnSettings(string $closeBtnSettings)
    {
        $this->closeBtnSettings = $closeBtnSettings;
    }

    /**
     * Returns the col1HeaderSettings
     *
     * @return string
     */
    public function getCol1HeaderSettings()
    {
        return $this->col1HeaderSettings;
    }

    /**
     * Sets the col1HeaderSettings
     *
     * @param string $col1HeaderSettings
     * @return void
     */
    public function setCol1HeaderSettings(string $col1HeaderSettings)
    {
        $this->col1HeaderSettings = $col1HeaderSettings;
    }

    /**
     * Returns the col2HeaderSettings
     *
     * @return string
     */
    public function getCol2HeaderSettings()
    {
        return $this->col2HeaderSettings;
    }

    /**
     * Sets the col2HeaderSettings
     *
     * @param string $col2HeaderSettings
     * @return void
     */
    public function setCol2HeaderSettings(string $col2HeaderSettings)
    {
        $this->col2HeaderSettings = $col2HeaderSettings;
    }

    /**
     * Returns the col3HeaderSettings
     *
     * @return string
     */
    public function getCol3HeaderSettings()
    {
        return $this->col3HeaderSettings;
    }

    /**
     * Sets the col3HeaderSettings
     *
     * @param string $col3HeaderSettings
     * @return void
     */
    public function setCol3HeaderSettings(string $col3HeaderSettings)
    {
        $this->col3HeaderSettings = $col3HeaderSettings;
    }

    /**
     * Returns the blocksTitle
     *
     * @return string
     */
    public function getBlocksTitle()
    {
        return $this->blocksTitle;
    }

    /**
     * Sets the blocksTitle
     *
     * @param string $blocksTitle
     * @return void
     */
    public function setBlocksTitle(string $blocksTitle)
    {
        $this->blocksTitle = $blocksTitle;
    }

    /**
     * Returns the blocksDescription
     *
     * @return string
     */
    public function getBlocksDescription()
    {
        return $this->blocksDescription;
    }

    /**
     * Sets the blocksDescription
     *
     * @param string $blocksDescription
     * @return void
     */
    public function setBlocksDescription(string $blocksDescription)
    {
        $this->blocksDescription = $blocksDescription;
    }

    /**
     * Returns the enabled
     *
     * @return bool enabled
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * Sets the enabled
     *
     * @param string $enabled
     * @return void
     */
    public function setEnabled(string $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Returns the primaryBtnRoleConsentModal
     *
     * @return string primaryBtnRoleConsentModal
     */
    public function getPrimaryBtnRoleConsentModal()
    {
        return $this->primaryBtnRoleConsentModal;
    }

    /**
     * Sets the primaryBtnRoleConsentModal
     *
     * @param string $primaryBtnRoleConsentModal
     * @return void
     */
    public function setPrimaryBtnRoleConsentModal(string $primaryBtnRoleConsentModal)
    {
        $this->primaryBtnRoleConsentModal = $primaryBtnRoleConsentModal;
    }

    /**
     * Returns the secondaryBtnRoleConsentModal
     *
     * @return string secondaryBtnRoleConsentModal
     */
    public function getSecondaryBtnRoleConsentModal()
    {
        return $this->secondaryBtnRoleConsentModal;
    }

    /**
     * Sets the secondaryBtnRoleConsentModal
     *
     * @param string $secondaryBtnRoleConsentModal
     * @return void
     */
    public function setSecondaryBtnRoleConsentModal(string $secondaryBtnRoleConsentModal)
    {
        $this->secondaryBtnRoleConsentModal = $secondaryBtnRoleConsentModal;
    }

    /**
     * Returns the tertiaryBtnRoleConsentModal
     *
     * @return string tertiaryBtnRoleConsentModal
     */
    public function getTertiaryBtnRoleConsentModal()
    {
        return $this->tertiaryBtnRoleConsentModal;
    }

    /**
     * Sets the tertiaryBtnRoleConsentModal
     *
     * @param string $tertiaryBtnRoleConsentModal
     * @return void
     */
    public function setTertiaryBtnRoleConsentModal(string $tertiaryBtnRoleConsentModal)
    {
        $this->tertiaryBtnRoleConsentModal = $tertiaryBtnRoleConsentModal;
    }

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
     * Returns the custombutton
     *
     * @return bool
     */
    public function getCustombutton()
    {
        return $this->custombutton;
    }

    /**
     * Sets the custombutton
     *
     * @param bool $custombutton
     * @return void
     */
    public function setCustombutton(bool $custombutton)
    {
        $this->custombutton = $custombutton;
    }

    /**
     * Returns the boolean state of custombutton
     *
     * @return bool
     */
    public function isCustombutton()
    {
        return $this->custombutton;
    }

    /**
     * Returns the customButtonHtml
     *
     * @return string
     */
    public function getCustomButtonHtml()
    {
        return $this->customButtonHtml;
    }

    /**
     * Sets the customButtonHtml
     *
     * @param string $customButtonHtml
     * @return void
     */
    public function setCustomButtonHtml(string $customButtonHtml)
    {
        $this->customButtonHtml = $customButtonHtml;
    }

    /**
     * Returns the inLineExecution
     *
     * @return bool
     */
    public function getInLineExecution()
    {
        return $this->inLineExecution;
    }

    /**
     * Sets the inLineExecution
     *
     * @param bool $inLineExecution
     * @return void
     */
    public function setInLineExecution(bool $inLineExecution)
    {
        $this->inLineExecution = $inLineExecution;
    }

    /**
     * Returns the boolean state of inLineExecution
     *
     * @return bool
     */
    public function isInLineExecution()
    {
        return $this->inLineExecution;
    }

    /**
     * Returns the layoutConsentModal
     *
     * @return string layoutConsentModal
     */
    public function getLayoutConsentModal()
    {
        return $this->layoutConsentModal;
    }

    /**
     * Sets the layoutConsentModal
     *
     * @param string $layoutConsentModal
     * @return void
     */
    public function setLayoutConsentModal(string $layoutConsentModal)
    {
        $this->layoutConsentModal = $layoutConsentModal;
    }

    /**
     * Returns the layoutSettings
     *
     * @return string layoutSettings
     */
    public function getLayoutSettings()
    {
        return $this->layoutSettings;
    }

    /**
     * Sets the layoutSettings
     *
     * @param string $layoutSettings
     * @return void
     */
    public function setLayoutSettings(string $layoutSettings)
    {
        $this->layoutSettings = $layoutSettings;
    }

    /**
     * Returns the positionConsentModal
     *
     * @return string positionConsentModal
     */
    public function getPositionConsentModal()
    {
        return $this->positionConsentModal;
    }

    /**
     * Sets the positionConsentModal
     *
     * @param string $positionConsentModal
     * @return void
     */
    public function setPositionConsentModal(string $positionConsentModal)
    {
        $this->positionConsentModal = $positionConsentModal;
    }

    /**
     * Returns the transitionConsentModal
     *
     * @return string transitionConsentModal
     */
    public function getTransitionConsentModal()
    {
        return $this->transitionConsentModal;
    }

    /**
     * Sets the transitionConsentModal
     *
     * @param string $transitionConsentModal
     * @return void
     */
    public function setTransitionConsentModal(string $transitionConsentModal)
    {
        $this->transitionConsentModal = $transitionConsentModal;
    }

    /**
     * Returns the transitionSettings
     *
     * @return string transitionSettings
     */
    public function getTransitionSettings()
    {
        return $this->transitionSettings;
    }

    /**
     * Sets the transitionSettings
     *
     * @param string $transitionSettings
     * @return void
     */
    public function setTransitionSettings(string $transitionSettings)
    {
        $this->transitionSettings = $transitionSettings;
    }

    /**
     * Returns the positionSettings
     *
     * @return string positionSettings
     */
    public function getPositionSettings()
    {
        return $this->positionSettings;
    }

    /**
     * Sets the positionSettings
     *
     * @param string $positionSettings
     * @return void
     */
    public function setPositionSettings(string $positionSettings)
    {
        $this->positionSettings = $positionSettings;
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

    /**
     * Returns the revisionText
     *
     * @return string
     */
    public function getRevisionText()
    {
        return $this->revisionText;
    }

    /**
     * Sets the revisionText
     *
     * @param string $revisionText
     * @return void
     */
    public function setRevisionText(string $revisionText)
    {
        $this->revisionText = $revisionText;
    }

    /**
     * Returns the impressLink
     *
     * @return string
     */
    public function getImpressLink(): string
    {
        return $this->impressLink;
    }

    /**
     * Sets the impressLink
     *
     * @param string $impressLink
     * @return void
     */
    public function setImpressLink(string $impressLink): void
    {
        $this->impressLink = $impressLink;
    }

    /**
     * Returns the impressText
     *
     * @return string
     */
    public function getImpressText(): string
    {
        return $this->impressText;
    }

    /**
     * Sets the impressText
     *
     * @param string $impressText
     * @return void
     */
    public function setImpressText(string $impressText): void
    {
        $this->impressText = $impressText;
    }

    /**
     * Returns the dataPolicyLink
     *
     * @return string
     */
    public function getDataPolicyLink(): string
    {
        return $this->dataPolicyLink;
    }

    /**
     * Sets the dataPolicyLink
     *
     * @param string $dataPolicyLink
     * @return void
     */
    public function setDataPolicyLink(string $dataPolicyLink): void
    {
        $this->dataPolicyLink = $dataPolicyLink;
    }

    /**
     * Returns the dataPolicyText
     *
     * @return string
     */
    public function getDataPolicyText(): string
    {
        return $this->dataPolicyText;
    }

    /**
     * Sets the dataPolicyText
     *
     * @param string $dataPolicyText
     * @return void
     */
    public function setDataPolicyText(string $dataPolicyText): void
    {
        $this->dataPolicyText = $dataPolicyText;
    }

}
