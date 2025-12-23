/**
 * Spinner Helper Module for cf_cookiemanager Backend
 *
 * Provides unified loading spinner management for TYPO3 backend modules.
 * Uses TYPO3's native <typo3-backend-spinner> web component.
 */

/**
 * Creates a TYPO3 backend spinner element
 *
 * @param {string} [size='small'] - Spinner size: 'small', 'medium', 'large'
 * @returns {HTMLElement} The spinner element
 */
export function createSpinner(size = 'small') {
    const spinner = document.createElement('typo3-backend-spinner');
    spinner.setAttribute('size', size);
    return spinner;
}

/**
 * Shows a spinner inside an element, replacing its content
 *
 * @param {HTMLElement} element - The container element
 * @param {string} [size='small'] - Spinner size
 * @returns {HTMLElement} The created spinner element
 */
export function showInElement(element, size = 'small') {
    const spinner = createSpinner(size);
    element.innerHTML = '';
    element.appendChild(spinner);
    return spinner;
}

/**
 * Replaces a button's content with a spinner and returns a restore function
 *
 * @param {HTMLButtonElement} button - The button element
 * @param {string} [size='small'] - Spinner size
 * @returns {{spinner: HTMLElement, restore: Function, setError: Function, setText: Function}}
 *          Object with spinner element and helper functions
 */
export function replaceButton(button) {
    const originalContent = button.innerHTML;
    const originalDisabled = button.disabled;

    const spinner = createSpinner('small');
    button.innerHTML = '';
    button.appendChild(spinner);
    button.disabled = true;

    return {
        spinner: spinner,

        /**
         * Restores the button to its original state
         */
        restore: function() {
            button.innerHTML = originalContent;
            button.disabled = originalDisabled;
        },

        /**
         * Shows an error state on the button
         * @param {string} [text='Error'] - Error text to display
         */
        setError: function(text = 'Error') {
            button.innerHTML = `<span style="color: red;">${text}</span>`;
            button.disabled = originalDisabled;
        },

        /**
         * Sets custom text on the button
         * @param {string} text - Text to display
         */
        setText: function(text) {
            button.innerHTML = text;
            button.disabled = originalDisabled;
        }
    };
}

/**
 * Shows or hides a spinner element by its ID
 *
 * @param {string} spinnerId - The spinner element's ID
 * @param {boolean} show - true to show, false to hide
 */
export function toggleById(spinnerId, show) {
    const spinner = document.getElementById(spinnerId);
    if (spinner) {
        spinner.style.display = show ? 'block' : 'none';
    }
}

/**
 * Shows a spinner by ID
 *
 * @param {string} spinnerId - The spinner element's ID
 */
export function show(spinnerId) {
    toggleById(spinnerId, true);
}

/**
 * Hides a spinner by ID
 *
 * @param {string} spinnerId - The spinner element's ID
 */
export function hide(spinnerId) {
    toggleById(spinnerId, false);
}

/**
 * Creates and shows a spinner next to an element, returning cleanup function
 *
 * @param {HTMLElement} element - Element to attach spinner to
 * @param {string} [position='after'] - Position: 'before' or 'after'
 * @param {string} [size='small'] - Spinner size
 * @returns {{spinner: HTMLElement, remove: Function}} Object with spinner and remove function
 */
export function attachTo(element, position = 'after', size = 'small') {
    const spinner = createSpinner(size);
    spinner.style.marginLeft = position === 'after' ? '8px' : '0';
    spinner.style.marginRight = position === 'before' ? '8px' : '0';

    if (position === 'before') {
        element.parentNode.insertBefore(spinner, element);
    } else {
        element.parentNode.insertBefore(spinner, element.nextSibling);
    }

    return {
        spinner: spinner,
        remove: function() {
            spinner.remove();
        }
    };
}
