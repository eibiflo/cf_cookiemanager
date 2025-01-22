import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import RegularEvent from '@typo3/core/event/regular-event.js';
import Modal from '@typo3/backend/modal.js';
import Severity from "@typo3/backend/severity.js";


new RegularEvent('click', function (e) {
    const currentStorage = e.target.dataset.cfStorage;
    e.target.style.display = 'none';
    const spinner = document.getElementById('loading-spinner');
    spinner.style.display = 'block';

    new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_installdatasets)
        .post({ storageUid: currentStorage })
        .then(async function (response) {
            const result = await response.resolve();
            e.target.style.display = 'block';
            spinner.style.display = 'none';
            if (result.insertSuccess) {
                Modal.confirm('Success', 'Datasets installed successfully.', Severity.success, [
                    {
                        text: 'Close',
                        trigger: function() {
                            Modal.dismiss();
                            location.reload();
                        }
                    }
                ]);
            } else {

                let message = 'Failed to install datasets.';
                if(result.error) {
                    message = result.error
                }

                Modal.confirm('Error', message, Severity.error, [
                    {
                        text: 'Close',
                        trigger: function() {
                            Modal.dismiss();
                        }
                    },
                    {
                        text: 'Do a Offline Installation',
                        btnClass: 'btn-primary',
                        trigger: function() {
                            Modal.dismiss();
                            document.getElementById('cf-standardDatasetInstall').style.display = 'none';
                            document.getElementById('cf-offlineDatasetInstall').style.display = 'block';

                        }
                    }
                ]);
            }
        })
        .catch(function (error) {
            console.error(error);
            spinner.style.display = 'none';
            Modal.confirm('Error', 'An error occurred while installing datasets.', Severity.error, [
                {
                    text: 'Close',
                    trigger: function() {
                        Modal.dismiss();
                    }
                }
            ]);
        });

}).bindTo(document.querySelector('.startConfiguration'));





new RegularEvent('click', function (e) {
    const fileInput = document.getElementById('datasetFile');
    const file = fileInput.files[0];
    const currentStorage = fileInput.dataset.cfStorage;

    if (file) {
        const formData = new FormData();
        formData.append('datasetFile', file);
        formData.append('storageUid', currentStorage);

        const spinner = document.getElementById('loading-spinner-offline');
        spinner.style.display = 'block';

        document.querySelector('.startConfigurationOffline').style.display = 'none';

        new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_uploaddataset)
            .post(formData)
            .then(async function (response) {
                const result = await response.resolve();
                spinner.style.display = 'none';
                if(result.uploadSuccess){
                    location.reload();
                }

            })
            .catch(function (error) {
                spinner.style.display = 'none';
                document.querySelector('.startConfigurationOffline').style.display = 'block';
                Modal.confirm('Error', 'An error occurred while uploading the dataset.', Severity.error, [
                    {
                        text: 'Close',
                        trigger: function() {
                            Modal.dismiss();
                        }
                    }
                ]);
            });
    }
}).bindTo(document.querySelector('.startConfigurationOffline'));