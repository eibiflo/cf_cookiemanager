import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import RegularEvent from '@typo3/core/event/regular-event.js';
import Modal from '@typo3/backend/modal.js';
import Severity from "@typo3/backend/severity.js";
import {unsafeHTML} from "lit/directives/unsafe-html.js";
import {html} from "lit";
import Diff from '@codingfreaks/cf-cookiemanager/Backend/BackendAjax/Thirdparty/diff.js';

function generateChangeHTML(item) {
    let changes = item.reviews;
    return `
        <div class="cf-cookiemanager-changes-modal-list-legend">
            <p class="fs-5">Below is an overview of the changes between Local and API data for each field. Changes in the "Local:" section are highlighted in <span style="background-color: green;">NEW</span> or <span style="background-color: red;">Removed</span>. <br>
            If a change is highlighted in RED, it will be removed during the update. If a change is highlighted in GREEN, it will be added during the update. <br>
            To ignore a dataset and make no changes, simply do nothing. To update a dataset, click on the "Update Dataset" button, and the API values will be applied to your local fields.</p>    
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
                        <div class="cf-cookiemanager-changes-modal-changes-api" >API: <div>${apiValue}</div></div>
                        <div class="cf-cookiemanager-changes-modal-changes-local">Local: <div>${localValue}</div></div>
                    </div>
                `;
    }).join('')}
        </div>
    `;
}

function createListItem(item) {

    const apiName = item.api ? (item.api.name || item.api.title) : '';
    const localName = item.local ? (item.local.name || item.local.title) : '';
    const displayName = apiName || localName || 'Unnamed';

    let listItemHTML =  `
        <div>${displayName}
        <span class="mx-1 badge badge-pill ${item.status === 'new' ? 'badge-success' : item.status === 'updated' ? 'badge-beta' : 'badge-danger'}"> ${item.status}</span>
        </div>
        <div class="cf-cookiemanager-buttons">
          ${item.status === 'updated' ? '<button class="cf-cookiemanager-see-more">Review changes</button>' : ''}
            <!-- <button class="cf-cookiemanager-ignore">Ignore</button> -->
          ${item.status === 'updated' ? '<button class="cf-cookiemanager-update">Update dataset</button>' : ''}
          ${item.status === 'new' ? '<button class="cf-cookiemanager-insert">Insert dataset</button>' : ''}

        </div>
    `;


    return listItemHTML;
}

function handleReviewChangesClick(item) {
    return function() {
        console.log(item);
        Modal.advanced({
            additionalCssClasses: ["cf-cookiemanager-review-modal"],
            buttons: [{
                btnClass: "btn-info float-start",
                name: "preview",
                icon: "actions-view",
                text: "Open in Cookie Database"
            }, {
                btnClass: "btn-success",
                name: "dismiss",
                icon: "actions-close",
                text: "Close",
                trigger: function(event, modal) {
                    modal.hideModal();
                }
            }],
            content: html`
                <h1>Review Changes</h1>
                ${unsafeHTML(generateChangeHTML(item))}
            `,
            size: Modal.sizes.full,
            title: "Review changes",
            staticBackdrop: true
        });
    };
}

function processChanges(result) {
    const changesContainer = document.createElement('div');
    changesContainer.id = 'changes-container';
    changesContainer.innerHTML = `<h3>Check for changes per language, between your Local data and Preset API:</h3>
    <div>
        <div class="d-flex mb-1">
            <span class="badge badge-pill badge-success">new</span>
            <p class="m-0 ms-2">New Datasets on API, not found in your Typo3 setup</p>
        </div>
        <div class="d-flex mb-1">
            <span class="badge badge-pill badge-beta">updated</span>
            <p class="m-0 ms-2">Changes in Datasets between API and your local setup (can be ignored, if you changed it, and do not want to use the API Data)</p>
        </div>
        <div class="d-flex mb-1">
            <span class="badge badge-pill badge-danger">notfound</span>
            <p class="m-0 ms-2">Not found on API, maybe removed or manually created by hand in your Configuration</p>
        </div>
    </p>

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

                    // Bind click event to the update button
                    new RegularEvent('click', function() {
                        const updateButton = listItem.querySelector('.cf-cookiemanager-update');
                        const spinner = document.createElement('typo3-backend-spinner');
                        spinner.setAttribute('size', 'small');
                        updateButton.innerHTML = ''; // Clear button content
                        updateButton.appendChild(spinner); // Show spinner

                        if (item.local && item.local.uid) {
                            new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_updatedataset)
                                .post({ datasetId: item.local.uid, entry: item.entry, changes: item.reviews })
                                .then(async function (response) {
                                    const result = await response.resolve();
                                    console.log('Dataset updated successfully:', result);
                                    listItem.remove(); // Remove the list item after a successful update
                                })
                                .catch(error => {
                                    console.error('Error updating dataset:', error);
                                    updateButton.innerHTML = '<span class="icon icon-error" style="color: red;"></span>'; // Show error icon
                                });
                        } else {
                            console.error('Error: datasetId is undefined or null');
                            updateButton.innerHTML = '<span class="icon icon-error" style="color: red;"></span>'; // Show error icon
                        }
                    }).bindTo(listItem.querySelector('.cf-cookiemanager-update'));

                    // Bind click event to the insert button
                    new RegularEvent('click', function() {
                        const insertButton = listItem.querySelector('.cf-cookiemanager-insert');
                        const spinner = document.createElement('typo3-backend-spinner');
                        spinner.setAttribute('size', 'small');
                        insertButton.innerHTML = ''; // Clear button content
                        insertButton.appendChild(spinner); // Show spinner

                        let storage = document.querySelector('#cf-start-update-check').dataset.cfStorage;

                        new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_insertdataset)
                            .post({ entry: item.entry, changes: item.api, languageKey:languageKey, storage: storage })
                            .then(async function (response) {
                                const result = await response.resolve();
                                console.log('Dataset inserted successfully:', result);
                                listItem.remove(); // Remove the list item after a successful insert
                            })
                            .catch(async error => {
                                const result = await error.resolve();
                                Modal.confirm('Dataset Warning', result.error, Severity.warning, [
                                    {
                                        text: 'Close',
                                        trigger: function() {
                                            Modal.dismiss();
                                        }
                                    }
                                ]);

                                insertButton.innerHTML = 'Try again'; // Show error icon
                            });
                    }).bindTo(listItem.querySelector('.cf-cookiemanager-insert'));

                    list.appendChild(listItem);

                    // Bind Modal to see changes in Detail if Update is needed
                    if(item.status === 'updated'){
                        new RegularEvent('click', handleReviewChangesClick(item)).bindTo(listItem.querySelector('.cf-cookiemanager-see-more'));
                    }

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

new RegularEvent('click', function (e) {
    const currentStorage = e.target.dataset.cfStorage;
    e.target.style.display = 'none';
    const spinner = document.getElementById('loading-spinner');
    spinner.style.display = 'block';

    new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_checkfordatabaseupdates)
        .withQueryArguments({storageUid: currentStorage})
        .get()
        .then(async function (response) {
            const result = await response.resolve();
            e.target.style.display = 'block';
            if(result.updatesAvailable === false){
                Modal.confirm('No Updates Available', 'No updates available for the current configuration.', Severity.info, [
                    {
                        text: 'Close',
                        trigger: function() {
                            Modal.dismiss();
                        }
                    }
                ]);
            }else{
                processChanges(result);
            }
            spinner.style.display = 'none';

        })
        .catch(function (error) {
            console.error(error);
            spinner.style.display = 'none';
        });

}).bindTo(document.getElementById('cf-start-update-check'));