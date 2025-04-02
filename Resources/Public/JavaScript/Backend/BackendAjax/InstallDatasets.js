import AjaxRequest from "@typo3/core/ajax/ajax-request.js";
import RegularEvent from '@typo3/core/event/regular-event.js';
import Modal from '@typo3/backend/modal.js';
import Severity from "@typo3/backend/severity.js";

/*
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
                Modal.advanced(
                    {
                        title: 'Success',
                        content: 'Datasets installed successfully.',
                        severity: Severity.success,
                        staticBackdrop: true,
                        buttons: [{
                            btnClass: "btn-success",
                            name: "dismiss",
                            icon: "actions-close",
                            text: "Finish Installation",
                            trigger: function(event, modal) {
                                modal.hideModal();
                                location.reload();
                            }
                        }]
                    }
                );
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
*/


new RegularEvent('click', function (e) {
    const currentStorage = e.target.dataset.cfStorage;
    const cfEndPoint = e.target.dataset.cfEndpoint;
    console.log(cfEndPoint);
    document.getElementById('cf-welcome-screen').style.display = 'none';
    document.getElementById('cf-onboarding-container').style.display = 'block';


    // Navigation zwischen Schritten
    const steps = document.querySelectorAll('.cf-step');
    const contents = document.querySelectorAll('.cf-step-content');
    const prevBtn = document.querySelector('.cf-prev-btn');
    const nextBtn = document.querySelector('.cf-next-btn');
    const installBtn = document.querySelector('.cf-install-btn');

    let currentStep = 1;

    function updateStep(step) {
        // Update Fortschrittsanzeige
        steps.forEach(s => {
            console.error(s.dataset.step);
            s.classList.remove('active');
            if (parseInt(s.dataset.step) <= step) {
                s.classList.add('completed');
            }
            if (parseInt(s.dataset.step) === step) {
                s.classList.add('active');
            }
        });

        // Update Content
        contents.forEach(c => {
            c.classList.remove('active');
            if (parseInt(c.dataset.step) === step) {
                c.classList.add('active');
            }
        });

        // Update Buttons
        prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
        nextBtn.style.display = step < 3 ? 'inline-block' : 'none';
        installBtn.style.display = step === 3 ? 'inline-block' : 'none';

        currentStep = step;
    }

    /* Event-Listener für Buttons */
    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            updateStep(currentStep - 1);
        }
    });



    nextBtn.addEventListener('click', () => {
        if (currentStep === 1) {
            const consentOptIn = document.getElementById('consentOptIn');
            const consentOptOut = document.getElementById('consentOptOut');

            if (!consentOptIn.checked && !consentOptOut.checked) {
                // Add error class to the consent options
                //document.querySelector('.cf-consent-option').classList.add('error');
                document.querySelectorAll(".cf-consent-option").forEach((el) => {
                    el.classList.add('error');
                });
                return; // Do not proceed to the next step
            } else {
                // Remove error class if it exists
                document.querySelectorAll(".cf-consent-option").forEach((el) => {
                    el.classList.remove('error');
                });
            }
            updateStep(currentStep + 1);
            return;
        }

        if (currentStep === 2) {
            // Validate API Key and Endpoint URL only if not empty
            const apiKey = document.getElementById('apiKey').value;
            const apiSecret = document.getElementById('apiSecret').value;
            const apiUrl = document.getElementById('endPointUrl').value;
            const currentStorage = document.getElementById('currentStorage').value;

            if (!apiKey && !apiSecret) {
                // If both API Key and Secret are empty, proceed without validation
                updateStep(currentStep + 1);
                return;
            }

            if (!apiKey || !apiSecret || !apiUrl) {
                // If either API Key, API Secret or API URL is empty, add error class and prevent navigation
                if (!apiKey) {
                    document.getElementById('apiKey').classList.add('error');
                } else {
                    document.getElementById('apiKey').classList.remove('error');
                }

                if (!apiSecret) {
                    document.getElementById('apiSecret').classList.add('error');
                } else {
                    document.getElementById('apiSecret').classList.remove('error');
                }

                if (!apiUrl) {
                    document.getElementById('endPointUrl').classList.add('error');
                } else {
                    document.getElementById('endPointUrl').classList.remove('error');
                }
                return; // Do not proceed to the next step
            } else {
                document.getElementById('apiKey').classList.remove('error');
                document.getElementById('apiSecret').classList.remove('error');
                document.getElementById('endPointUrl').classList.remove('error');
            }


            // Validate API Key and Endpoint URL by sending an AJAX request
            new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_checkapidata)
                .post({
                    apiKey: apiKey,
                    apiSecret: apiSecret,
                    endPointUrl: apiUrl,
                    currentStorage: currentStorage
                })
                .then(async function (response) {
                    const result = await response.resolve();
                    console.log(result);
                    if (result.integrationSuccess) {
                        updateStep(currentStep + 1);
                    } else {

                        Modal.confirm('Error', 'An error occurred while checking API Data:' + result.message, Severity.error, [
                            {
                                text: 'Close',
                                trigger: function() {
                                    Modal.dismiss();
                                }
                            }
                        ]);

                        console.error( result.message);
                     //   Modal.alert('API Validation Failed', result.message || 'Failed to validate API Key and Endpoint URL. Please check your credentials.', Severity.error);
                    }
                })
                .catch(function (error) {
                    console.error(error);
              //      Modal.alert('API Validation Error', 'An error occurred while validating the API Key and Endpoint URL. Please try again.', Severity.error);
                });

        }

        /*
        if (currentStep < 3) {
            updateStep(currentStep + 1);
        }
        */
    });

    // Installation starten
    installBtn.addEventListener('click', function() {
        // Konfiguration sammeln
        const config = {
            consentType: document.getElementById('consentType').value,
            //defaultLanguage: document.getElementById('defaultLanguage').value,
            //bannerPosition: document.querySelector('input[name="bannerPosition"]:checked').value,
            apiKey: document.getElementById('apiKey').value,
            endPointUrl: document.getElementById('endPointUrl').value,
            storageUid: currentStorage
        };

        // Spinner anzeigen
        this.style.display = 'none';
        const spinner = document.getElementById('loading-spinner') || document.createElement('div');
        if (!document.getElementById('loading-spinner')) {
            spinner.id = 'loading-spinner';
            spinner.innerHTML = 'Loading... <typo3-backend-spinner size="small"></typo3-backend-spinner>';
            onboardingContainer.querySelector('.cf-onboarding-actions').appendChild(spinner);
        }
        spinner.style.display = 'block';

        // Installation via AJAX durchführen
        new AjaxRequest(TYPO3.settings.ajaxUrls.cfcookiemanager_installdatasets)
            .post(config)
            .then(async function(response) {
                const result = await response.resolve();
                spinner.style.display = 'none';

                if (result.insertSuccess) {
                    Modal.advanced({
                        title: 'Installation erfolgreich',
                        content: 'Dein Cookie Manager wurde erfolgreich eingerichtet!',
                        severity: Severity.success,
                        staticBackdrop: true,
                        buttons: [{
                            btnClass: "btn-success",
                            name: "dismiss",
                            icon: "actions-close",
                            text: "Zum Dashboard",
                            trigger: function(event, modal) {
                                modal.hideModal();
                                location.reload();
                            }
                        }]
                    });
                } else {
                    let message = 'Die Installation konnte nicht abgeschlossen werden.';
                    if(result.error) {
                        message = result.error;
                    }

                    Modal.confirm('Fehler', message, Severity.error, [
                        {
                            text: 'Schließen',
                            trigger: function() {
                                Modal.dismiss();
                            }
                        },
                        {
                            text: 'Offline-Installation durchführen',
                            btnClass: 'btn-primary',
                            trigger: function() {
                                Modal.dismiss();
                                onboardingContainer.remove();
                                document.getElementById('cf-standardDatasetInstall').style.display = 'none';
                                document.getElementById('cf-offlineDatasetInstall').style.display = 'block';
                            }
                        }
                    ]);
                }
            })
            .catch(function(error) {
                console.error(error);
                spinner.style.display = 'none';
                installBtn.style.display = 'inline-block';

                Modal.confirm('Fehler', 'Bei der Installation ist ein Fehler aufgetreten.', Severity.error, [
                    {
                        text: 'Schließen',
                        trigger: function() {
                            Modal.dismiss();
                        }
                    }
                ]);
            });
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