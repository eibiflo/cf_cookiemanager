/**
 * AJAX Helper Module for cf_cookiemanager Backend
 *
 * Provides unified AJAX request handling with consistent error handling
 * and URL resolution for TYPO3 backend modules.
 */
import AjaxRequest from "@typo3/core/ajax/ajax-request.js";

/**
 * Resolves an AJAX URL from TYPO3.settings.ajaxUrls
 *
 * @param {string} routeKey - The AJAX route key (e.g., 'cfcookiemanager_checkfordatabaseupdates')
 * @returns {string|undefined} The resolved URL or undefined if not found
 */
export function resolveAjaxUrl(routeKey) {
    const urls = (typeof TYPO3 !== 'undefined' && TYPO3.settings && TYPO3.settings.ajaxUrls)
        ? TYPO3.settings.ajaxUrls
        : {};

    if (!routeKey) {
        return undefined;
    }

    // Direct match
    if (urls[routeKey]) {
        return urls[routeKey];
    }

    // Try with ajax prefix (legacy support)
    const ajaxKey = routeKey.replace('cfcookiemanager_', 'cfcookiemanager_ajax_');
    if (urls[ajaxKey]) {
        return urls[ajaxKey];
    }

    return undefined;
}

/**
 * Performs a GET request to a TYPO3 AJAX endpoint
 *
 * @param {string} routeKey - The AJAX route key
 * @param {Object} [params={}] - Query parameters to append
 * @returns {Promise<Object>} The parsed JSON response
 * @throws {Error} If the request fails or URL cannot be resolved
 */
export async function ajaxGet(routeKey, params = {}) {
    const url = resolveAjaxUrl(routeKey);

    if (!url) {
        throw new Error(`AJAX URL not found for route: ${routeKey}`);
    }

    const request = new AjaxRequest(url);

    if (Object.keys(params).length > 0) {
        request.withQueryArguments(params);
    }

    const response = await request.get();
    return response.resolve();
}

/**
 * Performs a POST request to a TYPO3 AJAX endpoint
 *
 * @param {string} routeKey - The AJAX route key
 * @param {Object|FormData} [data={}] - Data to send in the request body
 * @returns {Promise<Object>} The parsed JSON response
 * @throws {Error} If the request fails or URL cannot be resolved
 */
export async function ajaxPost(routeKey, data = {}) {
    const url = resolveAjaxUrl(routeKey);

    if (!url) {
        throw new Error(`AJAX URL not found for route: ${routeKey}`);
    }

    const response = await new AjaxRequest(url).post(data);
    return response.resolve();
}
