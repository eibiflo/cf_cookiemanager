<?php

namespace CodingFreaks\CfCookiemanager\RecordList;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use Psr\EventDispatcher\EventDispatcherInterface;

class CodingFreaksDatabaseRecordList extends DatabaseRecordList{

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        TranslationConfigurationProvider $translateTools,
        EventDispatcherInterface $eventDispatcher
    ) {

        parent::__construct($iconFactory,$uriBuilder,$translateTools,$eventDispatcher);
    }

    /**
     * Creates the listing of records from a single table
     *
     * @param string $table Table name
     * @param int $id Page id
     * @throws \UnexpectedValueException
     * @return string HTML table with the listing for the record.
     */
    public function getTable($table, $id)
    {
        // Finding the total amount of records on the page
        $queryBuilderTotalItems = $this->getQueryBuilder($table, $id, [], ['*'], false, 0, 1);
        $totalItems = (int)$queryBuilderTotalItems->count('*')
            ->executeQuery()
            ->fetchOne();
        if ($totalItems === 0) {
            return '';
        }
        // set the limits
        // Use default value and overwrite with page ts config and tca config depending on the current view
        // Force limit in range 5, 10000
        // default 100
        $itemsLimitSingleTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxSingleDBListItems'] ??
            $this->modTSconfig['itemsLimitSingleTable'] ??
            100
        ), 5, 10000);

        // default 20
        $itemsLimitPerTable = MathUtility::forceIntegerInRange((int)(
            $GLOBALS['TCA'][$table]['interface']['maxDBListItems'] ??
            $this->modTSconfig['itemsLimitPerTable'] ??
            20
        ), 5, 10000);

        // Set limit depending on the view (single table vs. default)
        $itemsPerPage = $this->table ? $itemsLimitSingleTable : $itemsLimitPerTable;

        // Set limit defined by calling code
        if ($this->showLimit) {
            $itemsPerPage = $this->showLimit;
        }

        // Set limit from search
        if ($this->searchString) {
            $itemsPerPage = $totalItems;
        }

        // Init
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'];
        $l10nEnabled = BackendUtility::isTableLocalizable($table);

        $this->fieldArray = $this->getColumnsToRender($table, true);
        // Creating the list of fields to include in the SQL query
        $selectFields = $this->getFieldsToSelect($table, $this->fieldArray);

        $firstElement = ($this->page - 1) * $itemsPerPage;
        if ($firstElement > 2 && $itemsPerPage > 0) {
            // Get the two previous rows for sorting if displaying page > 1
            $firstElement -= 2;
            $itemsPerPage += 2;
            $queryBuilder = $this->getQueryBuilder($table, $id, [], $selectFields, true, $firstElement, $itemsPerPage);
            $firstElement += 2;
            $itemsPerPage -= 2;
        } else {
            $queryBuilder = $this->getQueryBuilder($table, $id, [], $selectFields, true, $firstElement, $itemsPerPage);
        }

        $queryResult = $queryBuilder->executeQuery();
        $columnsOutput = '';
        $onlyShowRecordsInSingleTableMode = $this->listOnlyInSingleTableMode && !$this->table;
        // Fetch records only if not in single table mode
        if ($onlyShowRecordsInSingleTableMode) {
            $dbCount = $totalItems;
        } elseif ($firstElement + $itemsPerPage <= $totalItems) {
            $dbCount = $itemsPerPage + 2;
        } else {
            $dbCount = $totalItems - $firstElement + 2;
        }
        // If any records was selected, render the list:
        if ($dbCount === 0) {
            return '';
        }

        // Get configuration of collapsed tables from user uc
        $lang = $this->getLanguageService();

        $tableIdentifier = $table;
        // Use a custom table title for translated pages
        if ($table === 'pages' && $this->showOnlyTranslatedRecords) {
            // pages records in list module are split into two own sections, one for pages with
            // sys_language_uid = 0 "Page" and an own section for sys_language_uid > 0 "Page Translation".
            // This if sets the different title for the page translation case and a unique table identifier
            // which is used in DOM as id.
            $tableTitle = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:pageTranslation'));
            $tableIdentifier = 'pages_translated';
        } else {
            $tableTitle = htmlspecialchars($lang->sL($GLOBALS['TCA'][$table]['ctrl']['title']));
            if ($tableTitle === '') {
                $tableTitle = $table;
            }
        }

        $backendUser = $this->getBackendUserAuthentication();
        $tablesCollapsed = $backendUser->getModuleData('list') ?? [];
        $tableCollapsed = (bool)($tablesCollapsed[$tableIdentifier] ?? false);

        // Header line is drawn
        $theData = [];
        if ($this->disableSingleTableView) {
            $theData[$titleCol] = BackendUtility::wrapInHelp($table, '', $tableTitle) . ' (<span class="t3js-table-total-items">' . $totalItems . '</span>)';
        } else {
            $icon = $this->table // @todo separate table header from contract/expand link
                ? '<span title="' . htmlspecialchars($lang->getLL('contractView')) . '">' . $this->iconFactory->getIcon('actions-view-table-collapse', Icon::SIZE_SMALL)->render() . '</span>'
                : '<span title="' . htmlspecialchars($lang->getLL('expandView')) . '">' . $this->iconFactory->getIcon('actions-view-table-expand', Icon::SIZE_SMALL)->render() . '</span>';
            $theData[$titleCol] = $this->linkWrapTable($table, $tableTitle . ' (<span class="t3js-table-total-items">' . $totalItems . '</span>) ' . $icon);
        }
        $tableActions = '';
        if ($onlyShowRecordsInSingleTableMode) {
            $tableHeader = BackendUtility::wrapInHelp($table, '', $theData[$titleCol]);
        } else {
            $tableHeader = $theData[$titleCol];
            // Add the "new record" button
            $tableActions .= $this->createNewRecordButton($table);
            // Render collapse button if in multi table mode
            if (!$this->table) {
                $title = sprintf(htmlspecialchars($lang->getLL('collapseExpandTable')), $tableTitle);
                $icon = '<span class="collapseIcon">' . $this->iconFactory->getIcon(($tableCollapsed ? 'actions-view-list-expand' : 'actions-view-list-collapse'), Icon::SIZE_SMALL)->render() . '</span>';
                $tableActions .= '<button type="button"'
                    . ' class="btn btn-default btn-sm float-end t3js-toggle-recordlist"'
                    . ' title="' . $title . '"'
                    . ' aria-label="' . $title . '"'
                    . ' aria-expanded="' . ($tableCollapsed ? 'false' : 'true') . '"'
                    . ' data-table="' . htmlspecialchars($tableIdentifier) . '"'
                    . ' data-bs-toggle="collapse"'
                    . ' data-bs-target="#recordlist-' . htmlspecialchars($tableIdentifier) . '">'
                    . $icon
                    . '</button>';
            }
            // Show the select box
            $tableActions .= $this->columnSelector($table);
            // Create the Download button
            $tableActions .= $this->createDownloadButtonForTable($table, $totalItems);
        }
        $currentIdList = [];
        // Render table rows only if in multi table view or if in single table view
        $rowOutput = '';
        if (!$onlyShowRecordsInSingleTableMode || $this->table) {
            // Fixing an order table for sortby tables
            $this->currentTable = [];
            $allowManualSorting = ($GLOBALS['TCA'][$table]['ctrl']['sortby'] ?? false) && !$this->sortField;
            $prevUid = 0;
            $prevPrevUid = 0;
            // Get first two rows and initialize prevPrevUid and prevUid if on page > 1
            if ($firstElement > 2 && $itemsPerPage > 0) {
                $row = $queryResult->fetchAssociative();
                $prevPrevUid = -((int)$row['uid']);
                $row = $queryResult->fetchAssociative();
                $prevUid = $row['uid'];
            }
            $accRows = [];
            // Accumulate rows here
            while ($row = $queryResult->fetchAssociative()) {
                if (!$this->isRowListingConditionFulfilled($table, $row)) {
                    continue;
                }
                // In offline workspace, look for alternative record
                BackendUtility::workspaceOL($table, $row, $backendUser->workspace, true);
                if (is_array($row)) {
                    $accRows[] = $row;
                    $currentIdList[] = $row['uid'];
                    if ($allowManualSorting) {
                        if ($prevUid) {
                            $this->currentTable['prev'][$row['uid']] = $prevPrevUid;
                            $this->currentTable['next'][$prevUid] = '-' . $row['uid'];
                            $this->currentTable['prevUid'][$row['uid']] = $prevUid;
                        }
                        $prevPrevUid = isset($this->currentTable['prev'][$row['uid']]) ? -$prevUid : $row['pid'];
                        $prevUid = $row['uid'];
                    }
                }
            }
            // Render items:
            $this->CBnames = [];
            $this->duplicateStack = [];
            $cc = 0;

            // If no search happened it means that the selected
            // records are either default or All language and here we will not select translations
            // which point to the main record:
            $listTranslatedRecords = $l10nEnabled && $this->searchString === '' && !($this->hideTranslations === '*' || GeneralUtility::inList($this->hideTranslations, $table));
            //DebuggerUtility::var_dump($l10nEnabled && $this->searchString === '' && !($this->hideTranslations === '*'));
            foreach ($accRows as $row) {
                // Render item row if counter < limit
                if ($cc < $itemsPerPage) {
                    $cc++;
                    // Reset translations
                    $translations = [];
                    // Initialize with FALSE which causes the localization panel to not be displayed as
                    // the record is already localized, in free mode or has sys_language_uid -1 set.
                    // Only set to TRUE if TranslationConfigurationProvider::translationInfo() returns
                    // an array indicating the record can be translated.
                    $translationEnabled = false;
                    // Guard clause so we can quickly return if a record is localized to "all languages"
                    // It should only be possible to localize a record off default (uid 0)
                    if ($l10nEnabled && ($row[$GLOBALS['TCA'][$table]['ctrl']['languageField'] ?? null] ?? false) !== -1) {
                        $translationsRaw = $this->translateTools->translationInfo($table, $row['uid'], 0, $row, $selectFields);
                        if (is_array($translationsRaw)) {
                            $translationEnabled = true;
                            $translations = $translationsRaw['translations'] ?? [];
                        }
                    }

                    $rowOutput .= $this->renderListRow($table, $row, 0, $translations, $translationEnabled);
                    if ($listTranslatedRecords) {
                        foreach ($translations ?? [] as $lRow) {
                            if (!$this->isRowListingConditionFulfilled($table, $lRow)) {
                                continue;
                            }
                            // In offline workspace, look for alternative record:
                            BackendUtility::workspaceOL($table, $lRow, $backendUser->workspace, true);
                            if (is_array($lRow) && $backendUser->checkLanguageAccess($lRow[$GLOBALS['TCA'][$table]['ctrl']['languageField']])) {
                                $currentIdList[] = $lRow['uid'];
                                $rowOutput .= $this->renderListRow($table, $lRow, 18, [], false);
                            }
                        }
                    }
                }
            }
            // Record navigation is added to the beginning and end of the table if in single table mode
            if ($this->table) {
                $pagination = $this->renderListNavigation($this->table, $totalItems, $itemsPerPage);
                $rowOutput = $pagination . $rowOutput . $pagination;
            } elseif ($totalItems > $itemsLimitPerTable) {
                // Show that there are more records than shown
                $rowOutput .= '
                    <tr>
                        <td colspan="' . (count($this->fieldArray)) . '">
                            <a href="' . htmlspecialchars($this->listURL() . '&table=' . rawurlencode($tableIdentifier)) . '" class="btn btn-default">
                                ' . $this->iconFactory->getIcon('actions-caret-down', Icon::SIZE_SMALL)->render() . '
                                ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.expandTable') . '
                            </a>
                        </td>
                    </tr>';
            }
            // The header row for the table is now created
            $columnsOutput = $this->renderListHeader($table, $currentIdList);
        }

        // Initialize multi record selection actions
        $multiRecordSelectionActions = '';
        if ($this->noControlPanels === false) {
            $multiRecordSelectionActions = '
                <div class="col t3js-multi-record-selection-actions hidden">
                    <div class="row row-cols-auto align-items-center g-2">
                        <div class="col">
                            <strong>
                                ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '
                            </strong>
                        </div>
                        ' . $this->renderMultiRecordSelectionActions($table, $currentIdList) . '
                    </div>
                </div>
            ';
        }

        $collapseClass = $tableCollapsed && !$this->table ? 'collapse' : 'collapse show';
        $dataState = $tableCollapsed && !$this->table ? 'collapsed' : 'expanded';
        return '
            <div class="recordlist mb-5 mt-4 border" id="t3-table-' . htmlspecialchars($tableIdentifier) . '" data-multi-record-selection-identifier="t3-table-' . htmlspecialchars($tableIdentifier) . '">
                <form action="' . htmlspecialchars($this->listURL()) . '#t3-table-' . htmlspecialchars($tableIdentifier) . '" method="post" name="list-table-form-' . htmlspecialchars($tableIdentifier) . '">
                    <input type="hidden" name="cmd_table" value="' . htmlspecialchars($tableIdentifier) . '" />
                    <input type="hidden" name="cmd" />
                    <div class="recordlist-heading row m-0 p-2 g-0 gap-1 align-items-center ' . ($multiRecordSelectionActions !== '' ? 'multi-record-selection-panel' : '') . '">
                        ' . $multiRecordSelectionActions . '
                        <div class="col ms-2">
                            <span class="text-truncate">
                            ' . $tableHeader . '
                            </span>
                        </div>
                        <div class="col-auto">
                         ' . $tableActions . '
                        </div>
                    </div>
                    <div class="' . $collapseClass . '" data-state="' . $dataState . '" id="recordlist-' . htmlspecialchars($tableIdentifier) . '">
                        <div class="table-fit mb-0">
                            <table data-table="' . htmlspecialchars($tableIdentifier) . '" class="table table-striped table-hover mb-0">
                                <thead>
                                    ' . $columnsOutput . '
                                </thead>
                                <tbody data-multi-record-selection-row-selection="true">
                                    ' . $rowOutput . '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>
        ';
    }



    /**
     * Rendering a single row for the list
     *
     * @param string $table Table name
     * @param mixed[] $row Current record
     * @param int $indent Indent from left.
     * @param array $translations Array of already existing translations for the current record
     * @param bool $translationEnabled Whether the record can be translated
     * @return string Table row for the element
     * @internal
     * @see getTable()
     */
    public function renderListRow($table, array $row, int $indent, array $translations, bool $translationEnabled)
    {
        $titleCol = $GLOBALS['TCA'][$table]['ctrl']['label'] ?? '';
        $languageService = $this->getLanguageService();
        $rowOutput = '';
        $id_orig = $this->id;
        // If in search mode, make sure the preview will show the correct page
        if ((string)$this->searchString !== '') {
            $this->id = $row['pid'];
        }

        $tagAttributes = [
            'class' => [],
            'data-table' => $table,
            'title' => 'id=' . $row['uid'],
        ];
        // Add active class to record of current link
        if (
            isset($this->currentLink['tableNames'])
            && (int)$this->currentLink['uid'] === (int)$row['uid']
            && GeneralUtility::inList($this->currentLink['tableNames'], $table)
        ) {
            $tagAttributes['class'][] = 'active';
        }
        // Overriding with versions background color if any:
        if (!empty($row['_CSSCLASS'])) {
            $tagAttributes['class'] = [$row['_CSSCLASS']];
        }

        $tagAttributes['class'][] = 't3js-entity';

        // Preparing and getting the data-array
        $theData = [];
        $deletePlaceholderClass = '';
        foreach ($this->fieldArray as $fCol) {
            if ($fCol === $titleCol) {
                $recTitle = BackendUtility::getRecordTitle($table, $row, false, true);
                $warning = '';
                // If the record is edit-locked	by another user, we will show a little warning sign:
                $lockInfo = BackendUtility::isRecordLocked($table, $row['uid']);
                if ($lockInfo) {
                    $warning = '<span tabindex="0" data-bs-toggle="tooltip" data-bs-placement="right"'
                        . ' title="' . htmlspecialchars($lockInfo['msg']) . '"'
                        . ' aria-label="' . htmlspecialchars($lockInfo['msg']) . '">'
                        . $this->iconFactory->getIcon('warning-in-use', Icon::SIZE_SMALL)->render()
                        . '</span>';
                }
                if ($this->isRecordDeletePlaceholder($row)) {
                    // Delete placeholder records do not link to formEngine edit and are rendered strike-through
                    $deletePlaceholderClass = ' deletePlaceholder';
                    $theData[$fCol] = $theData['__label'] =
                        $warning
                        . '<span title="' . htmlspecialchars($languageService->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:row.deletePlaceholder.title')) . '">'
                        . htmlspecialchars($recTitle)
                        . '</span>';
                } else {
                    $theData[$fCol] = $theData['__label'] = $warning . $this->linkWrapItems($table, $row['uid'], $recTitle, $row);
                }
            } elseif ($fCol === 'pid') {
                $theData[$fCol] = $row[$fCol];
            } elseif ($fCol !== '' && $fCol === ($GLOBALS['TCA'][$table]['ctrl']['cruser_id'] ?? '')) {
                $theData[$fCol] = $this->getBackendUserInformation((int)$row[$fCol]);
            } elseif ($fCol === '_SELECTOR_') {
                if ($table !== 'pages' || !$this->showOnlyTranslatedRecords) {
                    // Add checkbox for all tables except the special page translations table
                    $theData[$fCol] = $this->makeCheckbox($table, $row);
                } else {
                    // Remove "_SELECTOR_", which is always the first item, from the field list
                    array_splice($this->fieldArray, 0, 1);
                }
            } elseif ($fCol === 'icon') {
                $iconImg = '
                    <span ' . BackendUtility::getRecordToolTip($row, $table) . ' ' . ($indent ? ' style="margin-left: ' . $indent . 'px;"' : '') . '>
                        ' . $this->iconFactory->getIconForRecord($table, $row, Icon::SIZE_SMALL)->render() . '
                    </span>';
                $theData[$fCol] = ($this->clickMenuEnabled && !$this->isRecordDeletePlaceholder($row)) ? BackendUtility::wrapClickMenuOnIcon($iconImg, $table, $row['uid']) : $iconImg;
            } elseif ($fCol === '_PATH_') {
                $theData[$fCol] = $this->recPath($row['pid']);
            } elseif ($fCol === '_REF_') {
                $theData[$fCol] = $this->generateReferenceToolTip($table, $row['uid']);
            } elseif ($fCol === '_CONTROL_') {
                $theData[$fCol] = $this->makeControl($table, $row);
            } elseif ($fCol === '_LOCALIZATION_') {
                // Language flag an title
                $theData[$fCol] = $this->languageFlag($table, $row);
                // Localize record
                $localizationPanel = $translationEnabled ? $this->makeLocalizationPanel($table, $row, $translations) : '';
                if ($localizationPanel !== '') {
                    $theData['_LOCALIZATION_b'] = '<div class="btn-group">' . $localizationPanel . '</div>';
                    $this->showLocalizeColumn[$table] = true;
                }
            } elseif ($fCol !== '_LOCALIZATION_b') {
                // default for all other columns, except "_LOCALIZATION_b"
                $pageId = $table === 'pages' ? $row['uid'] : $row['pid'];
                $tmpProc = BackendUtility::getProcessedValueExtra($table, $fCol, $row[$fCol], 100, $row['uid'], true, $pageId);
                $theData[$fCol] = $this->linkUrlMail(htmlspecialchars((string)$tmpProc), (string)($row[$fCol] ?? ''));
            }
        }
        // Reset the ID if it was overwritten
        if ((string)$this->searchString !== '') {
            $this->id = $id_orig;
        }
        // Add classes to table cells
        $this->addElement_tdCssClass['_SELECTOR_'] = 'col-selector';
        $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive' . $deletePlaceholderClass;
        $this->addElement_tdCssClass['__label'] = $this->addElement_tdCssClass[$titleCol];
        $this->addElement_tdCssClass['icon'] = 'col-icon';
        $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
        $this->addElement_tdCssClass['_PATH_'] = 'col-path';
        $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';
        $this->addElement_tdCssClass['_LOCALIZATION_b'] = 'col-localizationb';
        // Create element in table cells:
        $theData['uid'] = $row['uid'];
        if (isset($GLOBALS['TCA'][$table]['ctrl']['languageField'])
            && isset($GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'])
        ) {
            $theData['_l10nparent_'] = $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']];
        }

        $tagAttributes = array_map(
            static function ($attributeValue) {
                if (is_array($attributeValue)) {
                    return implode(' ', $attributeValue);
                }
                return $attributeValue;
            },
            $tagAttributes
        );

        $rowOutput .= $this->addElement($theData, GeneralUtility::implodeAttributes($tagAttributes, true));
        // Finally, return table row element:
        return $rowOutput;
    }



}