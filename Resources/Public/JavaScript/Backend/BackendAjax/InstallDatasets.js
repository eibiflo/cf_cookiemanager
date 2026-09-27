/**
 * Install Datasets Module for cf_cookiemanager Backend
 *
 * Handles the onboarding wizard for initial dataset installation
 * and offline dataset upload functionality.
 */
import RegularEvent from '@typo3/core/event/regular-event.js';
import { lll } from '@typo3/core/lit-helper.js';
import { ajaxPost } from '@codingfreaks/cf-cookiemanager/Backend/Utility/AjaxHelper.js';
import { showSuccess, showError, showConfirm, showWarning } from '@codingfreaks/cf-cookiemanager/Backend/Utility/ModalHelper.js';
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
 * Keeps the wizard choices across a page change, so leaving the module and coming
 * back does not silently reset them. Scoped per storage page.
 *
 * @param {string} currentStorage - Storage page uid
 * @returns {string} The sessionStorage key
 */
function wizardStateKey(currentStorage) {
    return 'cfCookiemanagerWizard_' + currentStorage;
}

/**
 * @param {string} currentStorage - Storage page uid
 * @returns {Object} The stored wizard choices, empty if none or storage is blocked
 */
function readWizardState(currentStorage) {
    try {
        return JSON.parse(sessionStorage.getItem(wizardStateKey(currentStorage)) || '{}') || {};
    } catch (e) {
        return {};
    }
}

/**
 * @param {string} currentStorage - Storage page uid
 */
function writeWizardState(currentStorage) {
    const consent = document.querySelector('input[name="consentType"]:checked');
    const scriptBlocking = document.getElementById('scriptBlocking');
    try {
        sessionStorage.setItem(wizardStateKey(currentStorage), JSON.stringify({
            consentType: consent ? consent.value : '',
            scriptBlocking: scriptBlocking ? scriptBlocking.checked : false
        }));
    } catch (e) {
        // Storage blocked, the wizard still works without it
    }
}

/**
 * Restores the wizard choices saved by writeWizardState.
 *
 * @param {string} currentStorage - Storage page uid
 */
function restoreWizardState(currentStorage) {
    const state = readWizardState(currentStorage);
    if (state.consentType) {
        document.querySelectorAll('input[name="consentType"]').forEach(input => {
            if (input.value === state.consentType) input.checked = true;
        });
    }
    const scriptBlocking = document.getElementById('scriptBlocking');
    if (scriptBlocking && typeof state.scriptBlocking === 'boolean') {
        scriptBlocking.checked = state.scriptBlocking;
    }
}

/**
 * Validates API credentials in step 2
 * @returns {{valid: boolean, apiKey: string, apiSecret: string, apiUrl: string, currentStorage: string}}
 */
function validateApiStep() {
    const apiUrl = document.getElementById('endPointUrl').value;
    const currentStorage = document.getElementById('currentStorage').value;

    // Credentials are already configured in the site settings, check those instead
    if (document.getElementById('apiCredentialsStored')) {
        return { valid: true, apiKey: '', apiSecret: '', apiUrl, currentStorage, skipValidation: false, stored: true };
    }

    const apiKey = document.getElementById('apiKey').value;
    const apiSecret = document.getElementById('apiSecret').value;

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
    const currentStorage = document.getElementById('currentStorage').value;

    document.getElementById('cf-welcome-screen').style.display = 'none';
    document.getElementById('cf-onboarding-container').style.display = 'block';

    const steps = document.querySelectorAll('.cf-step');
    const contents = document.querySelectorAll('.cf-step-content');
    const prevBtn = document.querySelector('.cf-prev-btn');
    const nextBtn = document.querySelector('.cf-next-btn');
    const installBtn = document.querySelector('.cf-install-btn');

    let currentStep = 1;
    updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);

    restoreWizardState(currentStorage);
    document.querySelectorAll('input[name="consentType"], #scriptBlocking').forEach(input => {
        input.addEventListener('change', () => writeWizardState(currentStorage));
    });

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

            if (validation.stored) {
                // The step is optional: a failed check is reported, but does not block the setup
                try {
                    const result = await ajaxPost('cfcookiemanager_checkapidata', {
                        useStoredCredentials: '1',
                        endPointUrl: validation.apiUrl,
                        currentStorage: validation.currentStorage
                    });
                    if (!result.integrationSuccess) {
                        showError(lll('js.error'), result.message || lll('js.install.apiValidateFailed'));
                    } else if (result.notices && result.notices.length) {
                        showWarning(lll('js.success'), result.message);
                    }
                } catch (error) {
                    console.error('API connection check error:', error);
                    showError(lll('js.install.apiValidationErrorTitle'), lll('js.install.apiValidationErrorMsg'));
                }
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
                    if (result.notices && result.notices.length) {
                        showWarning(lll('js.success'), result.message);
                    }
                    currentStep++;
                    updateStep(currentStep, steps, contents, prevBtn, nextBtn, installBtn);
                } else {
                    showError(lll('js.error'), result.message || lll('js.install.apiValidateFailed'));
                }
            } catch (error) {
                console.error('API validation error:', error);
                showError(lll('js.install.apiValidationErrorTitle'), lll('js.install.apiValidationErrorMsg'));
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
        // Without the checkbox nothing is sent, so the stored value stays as it is
        const scriptBlocking = document.getElementById('scriptBlocking');
        if (scriptBlocking) {
            config.scriptBlocking = scriptBlocking.checked ? '1' : '0';
        }

        this.style.display = 'none';
        toggleById('loading-spinner', true);

        try {
            const result = await ajaxPost('cfcookiemanager_installdatasets', config);

            if (result.insertSuccess) {
                try {
                    sessionStorage.removeItem(wizardStateKey(currentStorage));
                } catch (e) {
                    // Storage blocked, nothing to clean up
                }
                // The presets are installed even if a setting could not be saved, so say both
                const message = result.warning
                    ? lll('js.install.successMsg') + ' ' + result.warning
                    : lll('js.install.successMsg');
                showSuccess(lll('js.install.successTitle'), message, () => location.reload());
            } else {
                const message = result.error || lll('js.install.notSuccessful');
                showConfirm(lll('js.error'), message, [
                    {
                        text: lll('js.close'),
                        trigger: () => {
                            document.querySelector('.cf-install-btn').style.display = 'inline-block';
                        }
                    },
                    {
                        text: lll('js.install.startOffline'),
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
            showError(lll('js.error'), lll('js.install.error'));
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
            showError(lll('js.error'), lll('js.install.uploadFailed'));
            document.querySelector('.startConfigurationOffline').style.display = 'block';
        }
    } catch (error) {
        console.error('Upload error:', error);
        showError(lll('js.error'), lll('js.install.uploadError'));
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
