/**
 * Modal Helper Module for cf_cookiemanager Backend
 *
 * Provides unified modal dialog handling with consistent styling
 * and behavior for TYPO3 backend modules.
 */
import Modal from '@typo3/backend/modal.js';
import Severity from "@typo3/backend/severity.js";

/**
 * Shows a success dialog with optional callback on close
 *
 * @param {string} title - The modal title
 * @param {string} message - The modal content message
 * @param {Function|null} [onClose=null] - Optional callback executed when modal is closed
 */
export function showSuccess(title, message, onClose = null) {
    Modal.advanced({
        title: title,
        content: message,
        severity: Severity.ok,
        buttons: [{
            btnClass: "btn-success",
            name: "dismiss",
            icon: "actions-close",
            text: "Close",
            trigger: function(event, modal) {
                modal.hideModal();
                if (typeof onClose === 'function') {
                    onClose();
                }
            }
        }]
    });
}

/**
 * Shows an error dialog
 *
 * @param {string} title - The modal title
 * @param {string} message - The error message
 */
export function showError(title, message) {
    Modal.advanced({
        title: title,
        content: message,
        severity: Severity.error,
        buttons: [{
            btnClass: "btn-default",
            name: "dismiss",
            icon: "actions-close",
            text: "Close",
            trigger: function(event, modal) {
                modal.hideModal();
            }
        }]
    });
}

/**
 * Shows an info dialog
 *
 * @param {string} title - The modal title
 * @param {string} message - The info message
 */
export function showInfo(title, message) {
    Modal.confirm(title, message, Severity.info, [
        {
            text: 'Close',
            trigger: function() {
                Modal.dismiss();
            }
        }
    ]);
}

/**
 * Shows a warning dialog
 *
 * @param {string} title - The modal title
 * @param {string} message - The warning message
 */
export function showWarning(title, message) {
    Modal.confirm(title, message, Severity.warning, [
        {
            text: 'Close',
            trigger: function() {
                Modal.dismiss();
            }
        }
    ]);
}

/**
 * Shows a confirmation dialog with custom buttons
 *
 * @param {string} title - The modal title
 * @param {string} message - The modal message
 * @param {Array<{text: string, btnClass?: string, trigger: Function}>} buttons - Array of button configurations
 * @param {string} [severity='info'] - Severity level ('info', 'warning', 'error')
 */
export function showConfirm(title, message, buttons, severity = 'info') {
    const severityMap = {
        'info': Severity.info,
        'warning': Severity.warning,
        'error': Severity.error,
        'success': Severity.ok
    };

    Modal.confirm(title, message, severityMap[severity] || Severity.info, buttons);
}

/**
 * Shows an advanced modal with full customization
 *
 * @param {Object} options - Modal.advanced() options
 * @param {string} options.title - The modal title
 * @param {string|TemplateResult} options.content - The modal content (string or lit-html template)
 * @param {Array} [options.buttons] - Array of button configurations
 * @param {string} [options.size] - Modal size ('small', 'medium', 'large', 'full')
 * @param {Array<string>} [options.additionalCssClasses] - Additional CSS classes
 * @param {boolean} [options.staticBackdrop] - Prevent closing on backdrop click
 * @returns {Object} The modal instance
 */
export function showAdvanced(options) {
    const defaults = {
        staticBackdrop: false
    };

    // Map size string to Modal.sizes constant
    if (options.size && typeof options.size === 'string') {
        const sizeMap = {
            'small': Modal.sizes.small,
            'medium': Modal.sizes.medium,
            'large': Modal.sizes.large,
            'full': Modal.sizes.full
        };
        options.size = sizeMap[options.size] || options.size;
    }

    return Modal.advanced({
        ...defaults,
        ...options
    });
}

/**
 * Dismisses the currently open modal
 */
export function dismiss() {
    Modal.dismiss();
}
