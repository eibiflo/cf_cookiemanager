/**
 * Install Datasets Module for cf_cookiemanager Backend
 *
 * Handles the onboarding wizard for initial dataset installation
 * and offline dataset upload functionality.
 */
import RegularEvent from '@typo3/core/event/regular-event.js';
import { ajaxPost } from '@codingfreaks/cf-cookiemanager/Backend/Utility/AjaxHelper.js';
import { showSuccess, showError, showConfirm } from '@codingfreaks/cf-cookiemanager/Backend/Utility/ModalHelper.js';
import { toggleById } from '@codingfreaks/cf-cookiemanager/Backend/Utility/SpinnerHelper.js';

/**
 * Validates consent selection in step 1
 * @returns {boolean} True if valid
 */
function validateConsentStep() {
    const consentOptIn = document.getElementById('consentOptIn');
    const consentOptOut = document.getElementById('consentOptOut');

    if (!consentOptIn.checked && !consentOptOut.checked) {
        document.querySelectorAll('.cf-consent-option').forEach(el => el.classList.add('error'));
        return false;
    }

    document.querySelectorAll('.cf-consent-option').forEach(el => el.classList.remove('error'));
    return true;
}

/**
 * Validates API credentials in step 2
 * @returns {{valid: boolean, apiKey: string, apiSecret: string, apiUrl: string, currentStorage: string}}
 */
function validateApiStep() {
    const apiKey = document.getElementById('apiKey').value;
    const apiSecret = document.getElementById('apiSecret').value;
    const apiUrl = document.getElementById('endPointUrl').value;
    const currentStorage = document.getElementById('currentStorage').value;

    // If both API Key and Secret are empty, skip validation
    if (!apiKey && !apiSecret) {
        return { valid: true, apiKey, apiSecret, apiUrl, currentStorage, skipValidation: true };
    }

    // Validate all fields if any credential is provided
    let valid = true;

    ['apiKey', 'apiSecret', 'endPointUrl'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        const isEmpty = !field.value;
        field.classList.toggle('error', isEmpty);
        if (isEmpty) valid = false;
    });

    return { valid, apiKey, apiSecret, apiUrl, currentStorage, skipValidation: false };
}

/**
 * Updates the wizard step UI
 * @param {number} step - Current step number (1-3)
 * @param {NodeList} steps - Step indicator elements
 * @param {NodeList} contents - Step content elements
 * @param {HTMLElement} prevBtn - Previous button
 * @param {HTMLElement} nextBtn - Next button
 * @param {HTMLElement} installBtn - Install button
 */
function updateStep(step, steps, contents, prevBtn, nextBtn, installBtn) {
    steps.forEach(s => {
        s.classList.remove('active');
        const stepNum = parseInt(s.dataset.step);
        s.classList.toggle('completed', stepNum < step);
        if (stepNum === step) s.classList.add('active');
    });

    contents.forEach(c => {
        c.classList.toggle('active', parseInt(c.dataset.step) === step);
    });

    prevBtn.style.display = step > 1 ? 'inline-block' : 'none';
    nextBtn.style.display = step < 3 ? 'inline-block' : 'none';
    installBtn.style.display = step === 3 ? 'inline-block' : 'none';
}

/**
 * Main configuration wizard handler
 */
new RegularEvent('click', function(e) {
    const currentStorage = e.target.dataset.cfStorage;

    document.getElementById('cf-welcome-screen').style.display = 'none';
    document.getElementById('cf-onboarding-container').style.display = 'block';

    const steps = document.querySelectorAll('.cf-step');
    const contents = document.querySelectorAll('.cf-step-content');
    const prevBtn = document.querySelector('.cf-prev-btn');
    const nextBtn = document.querySelector('.cf-next-btn');
    const installBtn = document.querySelector('.cf-install-btn');

    let currentStep = 1;
    updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);

    // Previous button handler
    prevBtn.addEventListener('click', () => {
        if (currentStep > 1) {
            currentStep--;
            updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);
        }
    });

    // Next button handler
    nextBtn.addEventListener('click', async () => {
        if (currentStep === 1) {
            if (validateConsentStep()) {
                currentStep++;
                updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);
            }
            return;
        }

        if (currentStep === 2) {
            const validation = validateApiStep();

            if (!validation.valid) return;

            if (validation.skipValidation) {
                currentStep++;
                updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);
                return;
            }

            try {
                const result = await ajaxPost('cfcookiemanager_checkapidata', {
                    apiKey: validation.apiKey,
                    apiSecret: validation.apiSecret,
                    endPointUrl: validation.apiUrl,
                    currentStorage: validation.currentStorage
                });

                if (result.integrationSuccess) {
                    currentStep++;
                    updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);
                } else {
                    showError('Error', result.message || 'Failed to validate API credentials. Please check your settings or potential firewall issues.');
                }
            } catch (error) {
                console.error('API validation error:', error);
                showError('API Validation Error', 'An error occurred while validating the API credentials. Please try again.');
            }
        }
    });

    // Install button handler
    installBtn.addEventListener('click', async function() {
        const config = {
            consentType: document.querySelector('input[name="consentType"]:checked').value,
            endPointUrl: document.getElementById('endPointUrl').value,
            storageUid: currentStorage
        };

        this.style.display = 'none';
        toggleById('loading-spinner', true);

        try {
            const result = await ajaxPost('cfcookiemanager_installdatasets', config);

            if (result.insertSuccess) {
                showSuccess('Installation successful', 'Your Cookie-Manager is Ready!', () => location.reload());
            } else {
                const message = result.error || 'Installation was not successful.';
                showConfirm('Error', message, [
                    {
                        text: 'Close',
                        trigger: () => {
                            document.querySelector('.cf-install-btn').style.display = 'inline-block';
                        }
                    },
                    {
                        text: 'Start Offline-Installation',
                        btnClass: 'btn-primary',
                        trigger: () => {
                            document.getElementById('cf-standardDatasetInstall').style.display = 'none';
                            document.getElementById('cf-offlineDatasetInstall').style.display = 'block';
                        }
                    }
                ], 'error');
            }
        } catch (error) {
            console.error('Installation error:', error);
            showError('Error', 'Installation error. Please open an issue on Github with your error log.');
            this.style.display = 'inline-block';
        } finally {
            toggleById('loading-spinner', false);
        }
    });

}).bindTo(document.querySelector('.startConfiguration'));

/**
 * Offline dataset upload handler
 */
new RegularEvent('click', async function(e) {
    const fileInput = document.getElementById('datasetFile');
    const file = fileInput.files[0];
    const currentStorage = fileInput.dataset.cfStorage;

    if (!file) return;

    const formData = new FormData();
    formData.append('datasetFile', file);
    formData.append('storageUid', currentStorage);

    toggleById('loading-spinner-offline', true);
    document.querySelector('.startConfigurationOffline').style.display = 'none';

    try {
        const result = await ajaxPost('cfcookiemanager_uploaddataset', formData);

        if (result.uploadSuccess) {
            location.reload();
        } else {
            showError('Error', 'Dataset upload failed.');
            document.querySelector('.startConfigurationOffline').style.display = 'block';
        }
    } catch (error) {
        console.error('Upload error:', error);
        showError('Error', 'An error occurred while uploading the dataset.');
        document.querySelector('.startConfigurationOffline').style.display = 'block';
    } finally {
        toggleById('loading-spinner-offline', false);
    }
}).bindTo(document.querySelector('.startConfigurationOffline'));

/**
 * Open offline configuration handler
 */
new RegularEvent('click', function(e) {
    document.getElementById('cf-standardDatasetInstall').style.display = 'none';
    document.getElementById('cf-offlineDatasetInstall').style.display = 'block';
}).bindTo(document.querySelector('.openConfigurationOffline'));
