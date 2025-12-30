/**
 * Thumbnail Service Module for cf_cookiemanager Backend
 *
 * Handles thumbnail cache clearing and API connection testing.
 */
import RegularEvent from '@typo3/core/event/regular-event.js';
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
            showError('Error', 'Error clearing thumbnail cache. The cache may be empty or the cache folder is not writable.');
            return;
        }

        showSuccess('Success', 'Thumbnail cache cleared successfully.', () => location.reload());
    } catch (error) {
        console.error('Thumbnail cache clear error:', error);
        showError('Error', 'Error clearing thumbnail cache. Please check your logs.');
    } finally {
        toggleById('loading-spinner-thumbnail-cache', false);
    }
}).bindTo(document.getElementById('cf-clear-thumbnail-cache'));
