import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import RegularEvent from '@typo3/core/event/regular-event.js';
import Modal from '@typo3/backend/modal.js';
import Severity from "@typo3/backend/severity.js";

new RegularEvent('click', function() {
    const spinner = document.getElementById('loading-spinner-thumbnail-cache');
    spinner.style.display = 'block';

    new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_clearthumbnailcache)
        .post({})
        .then(async function(response) {
            const result = await response.resolve();


            if(result.clearSuccess === false) {
                Modal.advanced({
                    content: 'Error clearing thumbnail cache Request, maybe the cache is already empty or the cache folder is not writable.',
                    severity: Severity.error,
                    title: 'Error'
                });
                return;
            }

            Modal.advanced({
                content: 'Thumbnail cache cleared successfully.',
                severity: Severity.info,
                title: 'Success',
                buttons: [{
                    btnClass: "btn-success",
                    name: "dismiss",
                    icon: "actions-close",
                    text: "Close",
                    trigger: function(event, modal) {
                        modal.hideModal();
                        location.reload();
                    }
                }]
            });
        })
        .catch(function(error) {
            Modal.advanced({
                content: 'Error clearing thumbnail cache Request, check your logs.',
                severity: Severity.error,
                title: 'Error'
            });
        })
        .finally(function() {
            spinner.style.display = 'none';
        });
}).bindTo(document.getElementById('cf-clear-thumbnail-cache'));



new RegularEvent('click', function(e) {
    const spinner = document.getElementById('loading-spinner-api-connect');
    spinner.style.display = 'block';

    let storage = document.querySelector('#loading-spinner-api').dataset.cfStorage;

    new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_testapiconnection)
        .post({
            currentStorage: storage
        })
        .then(async function(response) {
            const result = await response.resolve();


            if(result.connectionSuccess === false) {
                Modal.advanced({
                    content: result.message,
                    severity: Severity.error,
                    title: 'Error'
                });
                return;
            }

            Modal.advanced({
                content: result.message,
                severity: Severity.info,
                title: 'Success',
                buttons: [{
                    btnClass: "btn-success",
                    name: "dismiss",
                    icon: "actions-close",
                    text: "Close",
                    trigger: function(event, modal) {
                        modal.hideModal();
                       // location.reload();
                    }
                }]
            });
        })
        .catch(function(error) {
            Modal.advanced({
                content: 'Error testing API connection, please check your settings.',
                severity: Severity.error,
                title: 'Error'
            });
        })
        .finally(function() {
            spinner.style.display = 'none';
        });
}).bindTo(document.getElementById('loading-spinner-api'));
