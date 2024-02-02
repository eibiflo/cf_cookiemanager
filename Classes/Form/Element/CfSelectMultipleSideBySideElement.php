<?php

namespace CodingFreaks\CfCookiemanager\Form\Element;

use TYPO3\CMS\Backend\Form\Behavior\OnFieldChangeTrait;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\Element\SelectMultipleSideBySideElement;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Render a widget with two boxes side by side.
 *
 * This is rendered for config type=select, renderType=CfSelectMultipleSideBySide set
 */
class CfSelectMultipleSideBySideElement extends SelectMultipleSideBySideElement
{
    use OnFieldChangeTrait;
    protected $iconFactory = IconFactory::class;

    /**
     * Render side by side element.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
    */
    public function render(): array
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $possibleItemMapper = [
            0 => "value",
            1 => "label",
            3 => "value",
            4 => "group",
        ];

        $filterTextfield = [];
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $elementName = $parameterArray['itemFormElName'];

        if ($config['readOnly'] ?? false) {
            // Early return for the relatively simple read only case
            return $this->renderReadOnly();
        }

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        $maxItems = $config['maxitems'];

        $size = (int)($config['size'] ?? 2);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }

        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $listOfSelectedValues = [];
        $selectedItemsHtml = [];
        foreach ($selectedItems as $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem[$possibleItemMapper[0]] == $itemValue) {
                    $title = $possibleItem[$possibleItemMapper[1]];
                    $listOfSelectedValues[] = $itemValue;
                    $selectedItemsHtml[] = '<option value="' . htmlspecialchars((string)$itemValue) . '" title="' . htmlspecialchars((string)$title) . '">' . htmlspecialchars($this->appendValueToLabelInDebugMode($title, $itemValue)) . '</option>';
                    break;
                }
            }
        }

        $selectableItemCounter = 0;
        $selectableItemGroupCounter = 0;
        $selectableItemGroups = [];
        $selectableItemsHtml = [];


        // Initialize groups
        foreach ($possibleItems as $possibleItem) {
            $disableAttributes = [];
            if (!$itemCanBeSelectedMoreThanOnce && in_array((string)$possibleItem[$possibleItemMapper[0]], $selectedItems, true)) {
                $disableAttributes = [
                    'disabled' => 'disabled',
                    'class' => 'hidden',
                ];
            }


            if ($possibleItem[$possibleItemMapper[3]] === '--div--') {
                if ($selectableItemCounter !== 0) {
                    $selectableItemGroupCounter++;
                }
            } else {
                if(empty($possibleItem[$possibleItemMapper[4]])){
                    $possibleItem[$possibleItemMapper[4]] = "unknown";
                }
                $selectableItemGroups[$selectableItemGroupCounter]['header']['title'] = $possibleItem[$possibleItemMapper[4]];
                if(!empty( $possibleItem[$possibleItemMapper[3]])){
                    $selectableItemGroups[$selectableItemGroupCounter]['items'][] = [
                        'label' => $this->appendValueToLabelInDebugMode($possibleItem[$possibleItemMapper[1]], $possibleItem[$possibleItemMapper[0]]),
                        'attributes' => array_merge(['title' => $possibleItem[$possibleItemMapper[1]], 'value' => $possibleItem[$possibleItemMapper[0]],"data-category" => $selectableItemGroups[$selectableItemGroupCounter]['header']['title']], $disableAttributes),
                        'category' =>  "unknown",
                    ];
                }else{
                    DebuggerUtility::var_dump($possibleItemMapper);
                    DebuggerUtility::var_dump($possibleItem);
                    $selectableItemGroups[$selectableItemGroupCounter]['items'][] = [
                        'label' => $this->appendValueToLabelInDebugMode($possibleItem[$possibleItemMapper[1]], $possibleItem[$possibleItemMapper[0]]),
                        'attributes' => array_merge(['title' => $possibleItem[$possibleItemMapper[1]], 'value' => $possibleItem[$possibleItemMapper[0]],"data-category" => ""], $disableAttributes),
                        'category' =>  "",
                    ];
                }
                // In case the item is not disabled, enable the group (if any)
                if ($disableAttributes === [] && isset($selectableItemGroups[$selectableItemGroupCounter]['header'])) {
                    $selectableItemGroups[$selectableItemGroupCounter]['header']['disabled'] = false;
                }
                $selectableItemCounter++;
            }
        }


        // Process groups
        foreach ($selectableItemGroups as $selectableItemGroup) {
            if (!is_array($selectableItemGroup['items'] ?? false) || $selectableItemGroup['items'] === []) {
                continue;
            }

            $optionGroup = isset($selectableItemGroup['header']);
            if ($optionGroup) {
                $selectableItemsHtml[] = '<optgroup label="' . htmlspecialchars($selectableItemGroup['header']['title']) . '"' . (($selectableItemGroup['header']['disabled'] ?? true) ? 'class="hidden" disabled="disabled"' : '') . '>';
            }

            foreach ($selectableItemGroup['items'] as $item) {

                $selectableItemsHtml[] = '
                    <option ' . GeneralUtility::implodeAttributes($item['attributes'], true) . '>
                        ' . htmlspecialchars($item['label']) . '
                    </option>';
            }

            if ($optionGroup) {
                $selectableItemsHtml[] = '</optgroup>';
            }
        }

        // Html stuff for filter and select filter on top of right side of multi select boxes
        $filterTextfield[] = '<span class="input-group input-group-sm">';
        $filterTextfield[] =    '<span class="input-group-text">';
        $filterTextfield[] =        '<span class="fa fa-filter"></span>';
        $filterTextfield[] =    '</span>';
        $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control" value="">';
        $filterTextfield[] = '</span>';

        $filterDropDownOptions = [];
        if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
            foreach ($config['multiSelectFilterItems'] as $optionElement) {
                $value = $languageService->sL($optionElement[0]);
                $label = $value;
                if (isset($optionElement[1]) && trim($optionElement[1]) !== '') {
                    $label = $languageService->sL($optionElement[1]);
                }
                $filterDropDownOptions[] = '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
            }
        }
        $filterHtml = [];
        $filterHtml[] = '<div class="form-multigroup-item-wizard">';
        if (!empty($filterDropDownOptions)) {
            $filterHtml[] = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         '<select class="form-select form-select-sm t3js-formengine-multiselect-filter-dropdown">';
            $filterHtml[] =             implode(LF, $filterDropDownOptions);
            $filterHtml[] =         '</select>';
            $filterHtml[] =     '</div>';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         implode(LF, $filterTextfield);
            $filterHtml[] =     '</div>';
            $filterHtml[] = '</div>';
        } else {
            $filterHtml[] = implode(LF, $filterTextfield);
        }
        $filterHtml[] = '</div>';

        $multipleAttribute = '';
        if ($maxItems !== 1 && $size !== 1) {
            $multipleAttribute = ' multiple="multiple"';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        [$fieldControlResult, $alternativeControlResult] = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);
        $alternativeFieldControlHtml = $alternativeControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $alternativeControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $selectedOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');
        $availableOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');

        $html = [];
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . (int)$itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<div class="form-multigroup-wrap t3js-formengine-field-group">';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selected'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           '<select';
        $html[] =                               ' id="' . $selectedOptionsFieldId . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="form-select"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectedItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--move">';
        $html[] =                           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-top"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-top', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-bottom', Icon::SIZE_SMALL)->render();
            $html[] =                           '</a>';
        }
        $html[] =                                $alternativeFieldControlHtml;
        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-option t3js-btn-removeoption"';
        $html[] =                                   ' data-fieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                                   ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
        $html[] =                               '>';
        $html[] =                                   $this->iconFactory->getIcon('actions-selection-delete', Icon::SIZE_SMALL)->render();
        $html[] =                               '</a>';
        $html[] =                           '</div>';
        $html[] =                       '</div>';
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.items'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           implode(LF, $filterHtml);
        $selectElementAttrs = array_merge(
            [
                'size' => $size,
                'id' => $availableOptionsFieldId,
                'class' => 'form-select t3js-formengine-select-itemstoselect',
                'data-relatedfieldname' => $elementName,
                'data-exclusivevalues' =>  $config['exclusiveKeys'] ?? '',
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $html[] =                           '<select ' . GeneralUtility::implodeAttributes($selectElementAttrs, true) . '>';
        $html[] =                               implode(LF, $selectableItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =                           '<div class="btn-group-vertical">';
            $html[] =                               $fieldControlHtml;
            $html[] =                           '</div>';
            $html[] =                       '</div>';
        }
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] =       '</div>';
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';
        $html[] = '</div>';


        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@codingfreaks/cf-cookiemanager/FormEngine/Element/CfSelectMultipleSideBySideElement.js'
        )->instance($selectedOptionsFieldId, $availableOptionsFieldId);

        /*
        $resultArray['requireJsModules'][] = JavaScriptModuleInstruction::forRequireJS(
            'TYPO3/CMS/CfCookiemanager/FormEngine/Element/CfSelectMultipleSideBySideElement'
        )->instance($selectedOptionsFieldId, $availableOptionsFieldId);
        */
        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }

/*
    public function render(): array
    {
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];

        if ($config['readOnly'] ?? false) {
            // Early return for the relatively simple read only case
            return $this->renderReadOnly();
        }

        $filterTextfield = [];
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $elementName = $parameterArray['itemFormElName'];

        $possibleItems = $config['items'];
        $selectedItems = $parameterArray['itemFormElValue'] ?: [];
        $maxItems = $config['maxitems'];

        $size = (int)($config['size'] ?? 2);
        $autoSizeMax = (int)($config['autoSizeMax'] ?? 0);
        if ($autoSizeMax > 0) {
            $size = MathUtility::forceIntegerInRange($size, 1);
            $size = MathUtility::forceIntegerInRange(count($selectedItems) + 1, $size, $autoSizeMax);
        }

        $itemCanBeSelectedMoreThanOnce = !empty($config['multiple']);

        $listOfSelectedValues = [];
        $selectedItemsHtml = [];
        foreach ($selectedItems as $itemValue) {
            foreach ($possibleItems as $possibleItem) {
                if ($possibleItem['value'] == $itemValue) {
                    $title = $possibleItem['label'];
                    $listOfSelectedValues[] = $itemValue;
                    $selectedItemsHtml[] = '<option value="' . htmlspecialchars((string)$itemValue) . '" title="' . htmlspecialchars((string)$title) . '">' . htmlspecialchars($this->appendValueToLabelInDebugMode($title, $itemValue)) . '</option>';
                    break;
                }
            }
        }

        $selectableItemCounter = 0;
        $selectableItemGroupCounter = 0;
        $selectableItemGroups = [];
        $selectableItemsHtml = [];

        // Initialize groups
        foreach ($possibleItems as $possibleItem) {
            $disableAttributes = [];
            if (!$itemCanBeSelectedMoreThanOnce && in_array((string)$possibleItem['value'], $selectedItems, true)) {
                $disableAttributes = [
                    'disabled' => 'disabled',
                    'class' => 'hidden',
                ];
            }
            if ($possibleItem['value'] === '--div--') {
                if ($selectableItemCounter !== 0) {
                    $selectableItemGroupCounter++;
                }
                $selectableItemGroups[$selectableItemGroupCounter]['header']['title'] = $possibleItem['label'];
            } else {
                $selectableItemGroups[$selectableItemGroupCounter]['items'][] = [
                    'label' => $this->appendValueToLabelInDebugMode($possibleItem['label'], $possibleItem['value']),
                    'attributes' => array_merge(['title' => $possibleItem['label'], 'value' => $possibleItem['value']], $disableAttributes),
                ];
                // In case the item is not disabled, enable the group (if any)
                if ($disableAttributes === [] && isset($selectableItemGroups[$selectableItemGroupCounter]['header'])) {
                    $selectableItemGroups[$selectableItemGroupCounter]['header']['disabled'] = false;
                }
                $selectableItemCounter++;
            }
        }

        // Process groups
        foreach ($selectableItemGroups as $selectableItemGroup) {
            if (!is_array($selectableItemGroup['items'] ?? false) || $selectableItemGroup['items'] === []) {
                continue;
            }

            $optionGroup = isset($selectableItemGroup['header']);
            if ($optionGroup) {
                $selectableItemsHtml[] = '<optgroup label="' . htmlspecialchars($selectableItemGroup['header']['title']) . '"' . (($selectableItemGroup['header']['disabled'] ?? true) ? 'class="hidden" disabled="disabled"' : '') . '>';
            }

            foreach ($selectableItemGroup['items'] as $item) {
                $selectableItemsHtml[] = '
                    <option ' . GeneralUtility::implodeAttributes($item['attributes'], true, true) . '>
                        ' . htmlspecialchars($item['label']) . '
                    </option>';
            }

            if ($optionGroup) {
                $selectableItemsHtml[] = '</optgroup>';
            }
        }

        // Html stuff for filter and select filter on top of right side of multi select boxes
        $filterTextfield[] = '<span class="input-group input-group-sm">';
        $filterTextfield[] =    '<span class="input-group-text">';
        $filterTextfield[] =        $this->iconFactory->getIcon('actions-filter', IconSize::SMALL)->render();
        $filterTextfield[] =    '</span>';
        $filterTextfield[] =    '<input class="t3js-formengine-multiselect-filter-textfield form-control" value="">';
        $filterTextfield[] = '</span>';

        $filterDropDownOptions = [];
        if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
            foreach ($config['multiSelectFilterItems'] as $optionElement) {
                $value = $languageService->sL($optionElement[0]);
                $label = $value;
                if (isset($optionElement[1]) && trim($optionElement[1]) !== '') {
                    $label = $languageService->sL($optionElement[1]);
                }
                $filterDropDownOptions[] = '<option value="' . htmlspecialchars($value) . '">' . htmlspecialchars($label) . '</option>';
            }
        }
        $filterHtml = [];
        $filterHtml[] = '<div class="form-multigroup-item-wizard">';
        if (!empty($filterDropDownOptions)) {
            $filterHtml[] = '<div class="t3js-formengine-multiselect-filter-container form-multigroup-wrap">';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         '<select class="form-select form-select-sm t3js-formengine-multiselect-filter-dropdown">';
            $filterHtml[] =             implode(LF, $filterDropDownOptions);
            $filterHtml[] =         '</select>';
            $filterHtml[] =     '</div>';
            $filterHtml[] =     '<div class="form-multigroup-item form-multigroup-element">';
            $filterHtml[] =         implode(LF, $filterTextfield);
            $filterHtml[] =     '</div>';
            $filterHtml[] = '</div>';
        } else {
            $filterHtml[] = implode(LF, $filterTextfield);
        }
        $filterHtml[] = '</div>';

        $multipleAttribute = '';
        if ($maxItems !== 1 && $size !== 1) {
            $multipleAttribute = ' multiple="multiple"';
        }

        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

        [$fieldControlResult, $alternativeControlResult] = $this->renderFieldControl();
        $fieldControlHtml = $fieldControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldControlResult, false);
        $alternativeFieldControlHtml = $alternativeControlResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $alternativeControlResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldWizardResult, false);

        $selectedOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');
        $availableOptionsFieldId = StringUtility::getUniqueId('tceforms-multiselect-');

        $html = [];
        $html[] = $this->renderLabel($selectedOptionsFieldId);
        $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
        $html[] =   $fieldInformationHtml;
        $html[] =   '<div class="form-wizards-wrap">';
        $html[] =       '<div class="form-wizards-element">';
        $html[] =           '<input type="hidden" data-formengine-input-name="' . htmlspecialchars($elementName) . '" value="' . (int)$itemCanBeSelectedMoreThanOnce . '" />';
        $html[] =           '<div class="form-multigroup-wrap t3js-formengine-field-group">';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selected'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           '<select';
        $html[] =                               ' id="' . $selectedOptionsFieldId . '"';
        $html[] =                               ' size="' . $size . '"';
        $html[] =                               ' class="form-select"';
        $html[] =                               $multipleAttribute;
        $html[] =                               ' data-formengine-input-name="' . htmlspecialchars($elementName) . '"';
        $html[] =                           '>';
        $html[] =                               implode(LF, $selectedItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--move">';
        $html[] =                           '<div class="btn-group-vertical">';
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-top"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_top')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-top', IconSize::SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-up"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_up')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-up', IconSize::SMALL)->render();
            $html[] =                           '</a>';
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-down"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_down')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-down', IconSize::SMALL)->render();
            $html[] =                           '</a>';
        }
        if ($maxItems > 1 && $size >= 5) {
            $html[] =                           '<a href="#"';
            $html[] =                               ' class="btn btn-default t3js-btn-option t3js-btn-moveoption-bottom"';
            $html[] =                               ' data-fieldname="' . htmlspecialchars($elementName) . '"';
            $html[] =                               ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.move_to_bottom')) . '"';
            $html[] =                           '>';
            $html[] =                               $this->iconFactory->getIcon('actions-move-to-bottom', IconSize::SMALL)->render();
            $html[] =                           '</a>';
        }
        $html[] =                                $alternativeFieldControlHtml;
        $html[] =                               '<a href="#"';
        $html[] =                                   ' class="btn btn-default t3js-btn-option t3js-btn-removeoption"';
        $html[] =                                   ' data-fieldname="' . htmlspecialchars($elementName) . '"';
        $html[] =                                   ' title="' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.remove_selected')) . '"';
        $html[] =                               '>';
        $html[] =                                   $this->iconFactory->getIcon('actions-selection-delete', IconSize::SMALL)->render();
        $html[] =                               '</a>';
        $html[] =                           '</div>';
        $html[] =                       '</div>';
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =               '<div class="form-multigroup-item form-multigroup-element">';
        $html[] =                   '<label>';
        $html[] =                       htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.items'));
        $html[] =                   '</label>';
        $html[] =                   '<div class="form-wizards-wrap form-wizards-aside">';
        $html[] =                       '<div class="form-wizards-element">';
        $html[] =                           implode(LF, $filterHtml);
        $selectElementAttrs = array_merge(
            [
                'size' => $size,
                'id' => $availableOptionsFieldId,
                'class' => 'form-select t3js-formengine-select-itemstoselect',
                'data-relatedfieldname' => $elementName,
                'data-exclusivevalues' =>  $config['exclusiveKeys'] ?? '',
                'data-formengine-validation-rules' => $this->getValidationDataAsJsonString($config),
            ],
            $this->getOnFieldChangeAttrs('change', $parameterArray['fieldChangeFunc'] ?? [])
        );
        $html[] =                           '<select ' . GeneralUtility::implodeAttributes($selectElementAttrs, true) . '>';
        $html[] =                               implode(LF, $selectableItemsHtml);
        $html[] =                           '</select>';
        $html[] =                       '</div>';
        if (!empty($fieldControlHtml)) {
            $html[] =                       '<div class="form-wizards-items-aside form-wizards-items-aside--field-control">';
            $html[] =                           '<div class="btn-group-vertical">';
            $html[] =                               $fieldControlHtml;
            $html[] =                           '</div>';
            $html[] =                       '</div>';
        }
        $html[] =                   '</div>';
        $html[] =               '</div>';
        $html[] =           '</div>';
        $html[] =           '<input type="hidden" name="' . htmlspecialchars($elementName) . '" value="' . htmlspecialchars(implode(',', $listOfSelectedValues)) . '" />';
        $html[] =       '</div>';
        if (!empty($fieldWizardHtml)) {
            $html[] = '<div class="form-wizards-items-bottom">';
            $html[] = $fieldWizardHtml;
            $html[] = '</div>';
        }
        $html[] =   '</div>';
        $html[] = '</div>';

        $resultArray['javaScriptModules'][] = JavaScriptModuleInstruction::create(
            '@typo3/backend/form-engine/element/select-multiple-side-by-side-element.js'
        )->instance($selectedOptionsFieldId, $availableOptionsFieldId);

        $resultArray['html'] = implode(LF, $html);
        return $resultArray;
    }
*/

}
