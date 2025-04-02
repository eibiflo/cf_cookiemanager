.. include:: ../Includes.txt


=============
Extension Settings
=============


Available Settings
------------------

The following settings can be configured in the admin backend module "settings", Extension Configuration.


Available Constants
------------------

    - disable_plugin (boolean): Disables the plugin in the frontend and middleware if set to 1.
    - script_blocking (boolean): Blocks third-party scripts and iframes. Only unregistered scripts/iframes are not loaded if the user has not given consent.
    (Feature): The loading of content such as iframes and scripts from third-party sources, can be Disabled by adding a Data Atribute to the Script or Iframe (data-script-blocking-disabled="true")

    - scan_api_key (string): Used for authorization/scan API (optional). Authorization on the API side is required to upgrade scan limits and using the Thumbnail API.
    - scan_api_secret (string): Used for authorization/scan API (optional). Authorization on the API side is required to upgrade scan limits and using the Thumbnail API.
    - end_point (string): Specifies the endpoint for the scan API (optional). The default value is https://coding-freaks.com/api.
    - thumbnail_api_enabled (boolean): Enables the thumbnail API. If active, the API is used to generate thumbnails for the iframe preview if content is blocked. (Uses external endpoint), the files are stored in /typo3temp/cfthumbnails/*

    - tracking_enabled (boolean): Enables cookie consent tracking. If active, the first action of the visitor in the consent modal is tracked before any external JavaScript is loaded.
    - tracking_obfuscate (boolean): Obfuscates the tracking data. If set to 1, the tracking js is obfuscated before it is sent to the browser. (uses javascript eval function)
    - autorun_consent (integer): Run Consent Modal on Page Load
    - force_consent (integer): Enable if you want to block page navigation until user consent action
    - revision_version (integer): Used for consent revision. If changed, all users will need to opt-in again.
    - cookie_expiration (integer): Specifies the number of days before the cookie expires. The default value is 365 days (one year).
    - cookie_path (string): Specifies the path where the cookie will be set. The default value is /.
    - hide_from_bots (boolean): If set to 1, the cookie plugin will not run when a bot/crawler/webdriver is detected.

    - cf_consentmodal_template (string): Specifies the path to the consent modal template in the extension.
    - cf_settingsmodal_template (string): Specifies the path to the settings modal template in the extension.
    - cf_settingsmodal_category_template (string): Specifies the path to the settings modal category item template in the extension.


Note that these settings are provided as an example and may vary depending on the version of the extension you are using.