/**
 * Thumbnail Service Module for cf_cookiemanager Backend
 *
 * Handles thumbnail cache clearing and API connection testing.
 */
import RegularEvent from '@typo3/core/event/regular-event.js';
import { lll } from '@typo3/core/lit-helper.js';
import { ajaxPost } from '@codingfreaks/cf-cookiemanager/Backend/Utility/AjaxHelper.js';
import { showSuccess, showError } from '@codingfreaks/cf-cookiemanager/Backend/Utility/ModalHelper.js';
import { toggleById } from '@codingfreaks/cf-cookiemanager/Backend/Utility/SpinnerHelper.js';

/**
 * Clear thumbnail cache handler
 */
new RegularEvent('click', async function() {
    toggleById('loading-spinner-thumbnail-cache', true);

    try {
        const result = await ajaxPost('cfcookiemanager_clearthumbnailcache', {});

        if (result.clearSuccess === false) {
            showError(lll('js.error'), lll('js.thumbnail.clearErrorEmpty'));
            return;
        }

        showSuccess(lll('js.success'), lll('js.thumbnail.clearSuccess'), () => location.reload());
    } catch (error) {
        console.error('Thumbnail cache clear error:', error);
        showError(lll('js.error'), lll('js.thumbnail.clearErrorLogs'));
    } finally {
        toggleById('loading-spinner-thumbnail-cache', false);
    }
}).bindTo(document.getElementById('cf-clear-thumbnail-cache'));
