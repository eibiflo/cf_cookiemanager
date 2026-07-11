/**
 * Update Check Module for cf_cookiemanager Backend
 *
 * Handles database update checks, displaying differences between
 * local and API data, and managing dataset updates/inserts.
 */
import RegularEvent from '@typo3/core/event/regular-event.js';
import { lll } from '@typo3/core/lit-helper.js';
import { html } from "lit";
import { unsafeHTML } from "lit/directives/unsafe-html.js";
import Diff from '@codingfreaks/cf-cookiemanager/Backend/BackendAjax/Thirdparty/diff.js';
import { ajaxGet, ajaxPost } from '@codingfreaks/cf-cookiemanager/Backend/Utility/AjaxHelper.js';
import { showInfo, showWarning, showAdvanced } from '@codingfreaks/cf-cookiemanager/Backend/Utility/ModalHelper.js';
import { toggleById, replaceButton } from '@codingfreaks/cf-cookiemanager/Backend/Utility/SpinnerHelper.js';
import { showSuccess, showError } from '@codingfreaks/cf-cookiemanager/Backend/Utility/ModalHelper.js';

/**
 * Generates HTML for displaying field changes with diff highlighting
 * @param {Object} item - The change item with reviews
 * @returns {string} HTML string with diff display
 */
function generateChangeHTML(item) {
    const changes = item.reviews;
    return `
        <div class="cf-cookiemanager-changes-modal-list-legend">
            <p class="fs-5">${lll('js.updateCheck.legend')}</p>
        </div>
        <div class="cf-cookiemanager-changes-modal-list">
            ${Object.entries(changes).map(([field, values]) => {
                const apiValue = values.api || '';
                const localValueTmp = values.local || '';
                const diff = Diff.diffWords(localValueTmp, apiValue);
                const localValue = diff.map(part => {
                    if (part.added) return `<span style="background-color: green;">${part.value}</span>`;
                    if (part.removed) return `<span style="background-color: red;">${part.value}</span>`;
                    return part.value;
                }).join('');
                return `
                    <div class="cf-cookiemanager-changes-modal-listitem">
                        <strong>${field}:</strong>
                        <div class="cf-cookiemanager-changes-modal-changes-api">${lll('js.updateCheck.apiPrefix')} <div>${apiValue}</div></div>
                        <div class="cf-cookiemanager-changes-modal-changes-local">${lll('js.updateCheck.localPrefix')} <div>${localValue}</div></div>
                    </div>
                `;
            }).join('')}
        </div>
    `;
}

/**
 * Creates HTML for a single list item
 * @param {Object} item - The change item
 * @returns {string} HTML string for the list item
 */
function createListItem(item) {
    const apiName = item.api ? (item.api.name || item.api.title) : '';
    const localName = item.local ? (item.local.name || item.local.title) : '';
    const displayName = apiName || localName || lll('js.updateCheck.unnamed');
    const displayNameClass = item.recordLink ? 'cf-cookiemanager-change-list-item-name' : '';

    const badgeClass = item.status === 'new' ? 'badge-success' :
                       item.status === 'updated' ? 'badge-beta' : 'badge-danger';

    return `
        <div>
            <span class="${displayNameClass}">${displayName}</span>
            <span class="mx-1 badge badge-pill ${badgeClass}">${item.status}</span>
        </div>
        <div class="cf-cookiemanager-buttons">
            ${item.status === 'updated' ? `<button class="cf-cookiemanager-see-more">${lll('js.updateCheck.reviewChanges')}</button>` : ''}
            ${item.status === 'updated' ? `<button class="cf-cookiemanager-update">${lll('js.updateCheck.updateDataset')}</button>` : ''}
            ${item.status === 'new' ? `<button class="cf-cookiemanager-insert">${lll('js.updateCheck.insertDataset')}</button>` : ''}
        </div>
    `;
}

/**
 * Creates a click handler for the review changes button
 * @param {Object} item - The change item
 * @returns {Function} Click handler function
 */
function handleReviewChangesClick(item) {
    return function() {
        showAdvanced({
            additionalCssClasses: ["cf-cookiemanager-review-modal"],
            buttons: [{
                btnClass: "btn-info float-start",
                name: "preview",
                icon: "actions-view",
                text: lll('js.updateCheck.openInDatabase'),
                trigger: function(event, modal) {
                    window.open(`https://coding-freaks.com/cookie-database/cookie-service/${item.api.identifier}`);
                }
            }, {
                btnClass: "btn-success",
                name: "dismiss",
                icon: "actions-close",
                text: lll('js.close'),
                trigger: function(event, modal) {
                    modal.hideModal();
                }
            }],
            content: html`
                <h1>${lll('js.updateCheck.reviewChangesHeading')}</h1>
                ${unsafeHTML(generateChangeHTML(item))}
            `,
            size: 'full',
            title: lll('js.updateCheck.reviewChanges'),
            staticBackdrop: true
        });
    };
}

/**
 * Binds update button functionality to a list item
 * @param {HTMLElement} listItem - The list item element
 * @param {Object} item - The change item data
 */
function bindUpdateButton(listItem, item) {
    const updateButton = listItem.querySelector('.cf-cookiemanager-update');
    if (!updateButton) return;

    new RegularEvent('click', async function() {
        const buttonState = replaceButton(updateButton);

        if (!item.local || !item.local.uid) {
            console.error('Error: datasetId is undefined or null');
            buttonState.setError(lll('js.error'));
            return;
        }

        try {
            const result = await ajaxPost('cfcookiemanager_uploaddataset', {
                datasetId: item.local.uid,
                entry: item.entry,
                changes: item.reviews
            });
            console.log('Dataset updated successfully:', result);
            listItem.remove();
        } catch (error) {
            console.error('Error updating dataset:', error);
            buttonState.setError(lll('js.error'));
        }
    }).bindTo(updateButton);
}

/**
 * Binds insert button functionality to a list item
 * @param {HTMLElement} listItem - The list item element
 * @param {Object} item - The change item data
 * @param {string} languageKey - The language key
 */
function bindInsertButton(listItem, item, languageKey) {
    const insertButton = listItem.querySelector('.cf-cookiemanager-insert');
    if (!insertButton) return;

    new RegularEvent('click', async function() {
        const buttonState = replaceButton(insertButton);

        const storage = document.querySelector('#cf-start-update-check').dataset.cfStorage;
        const endPointURL = document.querySelector('#cf-start-update-check').dataset.cfEndpoint;

        try {
            const result = await ajaxPost('cfcookiemanager_insertdataset', {
                entry: item.entry,
                changes: item.api,
                languageKey: languageKey,
                storageUid: storage,
                endPointURL: endPointURL
            });
            console.log('Dataset inserted successfully:', result);
            listItem.remove();
        } catch (error) {
            console.error('Error inserting dataset:', error);
            const errorResult = await error.resolve?.() || { error: lll('js.updateCheck.unknownError') };
            showWarning(lll('js.updateCheck.datasetWarning'), errorResult.error);
            buttonState.setText(lll('js.updateCheck.tryAgain'));
        }
    }).bindTo(insertButton);
}

/**
 * Processes and displays the changes result
 * @param {Object} result - The API result containing changes
 */
function processChanges(result) {
    const changesContainer = document.createElement('div');
    changesContainer.id = 'changes-container';
    changesContainer.innerHTML = `
        <h3>${lll('js.updateCheck.heading')}</h3>
        <div>
            <div class="d-flex mb-1">
                <span class="badge badge-pill badge-success">${lll('js.updateCheck.legendNew')}</span>
                <p class="m-0 ms-2">${lll('js.updateCheck.legendNewDesc')}</p>
            </div>
            <div class="d-flex mb-1">
                <span class="badge badge-pill badge-beta">${lll('js.updateCheck.legendUpdated')}</span>
                <p class="m-0 ms-2">${lll('js.updateCheck.legendUpdatedDesc')}</p>
            </div>
            <div class="d-flex mb-1">
                <span class="badge badge-pill badge-danger">${lll('js.updateCheck.legendNotfound')}</span>
                <p class="m-0 ms-2">${lll('js.updateCheck.legendNotfoundDesc')}</p>
            </div>
        </div>
    `;

    const changes = result.changes;

    for (const [languageKey, languageChanges] of Object.entries(changes)) {
        const section = document.createElement('div');
        section.className = 'cf-cookiemanager-change-section';
        section.innerHTML = `<div class="cf-cookiemanager-language"><div class="cf-cookiemanager-language-inner">${result.languages[languageKey].title}:</div></div>`;

        for (const [entryPoint, value] of Object.entries(languageChanges)) {
            const subSection = document.createElement('div');
            subSection.className = 'cf-cookiemanager-change-subsection';
            subSection.innerHTML = `<div class="cf-cookiemanager-change-subsection-title">${entryPoint}: <span class="badge">${Array.isArray(value) ? value.length : 0}</span></div>`;

            const list = document.createElement('div');
            list.className = 'cf-cookiemanager-change-list';

            if (Array.isArray(value)) {
                value.forEach(item => {
                    const listItem = document.createElement('div');
                    listItem.className = 'cf-cookiemanager-change-list-item';
                    listItem.innerHTML = createListItem(item);

                    // Bind record link click
                    if (item.recordLink) {
                        const nameElement = listItem.querySelector('.cf-cookiemanager-change-list-item-name');
                        nameElement?.addEventListener('click', function(event) {
                            event.preventDefault();
                            window.open(item.recordLink, 'popup', 'width=800,height=600');
                        });
                    }

                    // Bind action buttons
                    bindUpdateButton(listItem, item);
                    bindInsertButton(listItem, item, languageKey);

                    // Bind review changes modal
                    if (item.status === 'updated') {
                        new RegularEvent('click', handleReviewChangesClick(item))
                            .bindTo(listItem.querySelector('.cf-cookiemanager-see-more'));
                    }

                    list.appendChild(listItem);
                });
            }

            subSection.appendChild(list);
            section.appendChild(subSection);
        }

        changesContainer.appendChild(section);
    }

    const updateList = document.getElementById('cf-api-database-update-list');
    updateList.innerHTML = '';
    updateList.appendChild(changesContainer);
}

/**
 * Main update check handler
 */
new RegularEvent('click', async function(e) {
    const currentStorage = e.target.dataset.cfStorage;
    const endPointURL = document.querySelector('#cf-start-update-check').dataset.cfEndpoint;

    e.target.style.display = 'none';
    toggleById('loading-spinner', true);

    try {
        const result = await ajaxGet('cfcookiemanager_checkfordatabaseupdates', {
            storageUid: currentStorage,
            endPointURL: endPointURL
        });

        e.target.style.display = 'block';

        if (result.updatesAvailable === false) {
            const message = result.error || lll('js.updateCheck.noUpdatesMsg');
            showInfo(lll('js.updateCheck.noUpdatesTitle'), message);
        } else {
            processChanges(result);
        }
    } catch (error) {
        console.error('Update check error:', error);
        e.target.style.display = 'block';
    } finally {
        toggleById('loading-spinner', false);
    }
}).bindTo(document.getElementById('cf-start-update-check'));



/**
 * Test API connection handler
 */
new RegularEvent('click', async function(e) {
    toggleById('loading-spinner-api-connect', true);

    const storage = document.querySelector('#loading-spinner-api').dataset.cfStorage;

    try {
        const result = await ajaxPost('cfcookiemanager_testapiconnection', {
            currentStorage: storage
        });

        if (result.connectionSuccess === false) {
            showError(lll('js.error'), result.message);
            return;
        }

        showSuccess(lll('js.success'), result.message);
    } catch (error) {
        console.error('API connection test error:', error);
        showError(lll('js.error'), lll('js.updateCheck.apiConnError'));
    } finally {
        toggleById('loading-spinner-api-connect', false);
    }
}).bindTo(document.getElementById('loading-spinner-api'));
