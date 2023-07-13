.. include:: ../Includes.txt

.. _changelog:

==========
Change log
==========

Version 1.2.0 - Backend UI Improvements and Guided Tours beta
________________

Added Beta Guided Tours to the Cookiemanager Backend Module, to help you get started with the extension.

Improved the Backend Home UI, to make it more user friendly.

Fixed a bug in the "create new" viewhelper, that caused a wrong pid in new records.

Fixed a TODO in the CookieService selection, now its possible to filter tough the Cookie selection.

Fixed a bug in the Cookie API import, added the missing isRegex flag for regex cookie detection.


Version 1.1.6 - Bugfixes in Update Wizard
________________

Fixed a bug in the Update Wizard, that caused a wrong API import if the locale was not installed on the server.

Writing Functional Tests for: Update Wizard and RenderUtility

Improved Release workflow with Github Actions

Version 1.1.5 - Merge
________________

Merge RenderUtility PSR-14

Added Skip LIBXML_NOERROR to RenderUtility



Version 1.1.4 - Merge
________________

Merge RenderUtility


Version 1.1.3 - Features (PSR-14 Support)
________________

Added a new Event Dispatcher to Classify the Content if needed.

Marked @Hook $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/cf-cookiemanager']['classifyContent'] as deprecated, will be removed in next major release.

Fixed bug through adding html check in RenderUtility

Version 1.1.2 - Version fix
________________

Fixed missing version in ext_emconf.php


Version 1.1.1 - Bugfix in RenderUtility
________________

Fixed a bug in RenderUtility, that caused a wrong encoding of UTF-8 characters in the renderer.


Version 1.1.0 - New API Cookie Scanner
________________

API V2 is now available, with a new Cookie Scanner, to scan your website with a better performance and a new detection algorithm.

Ngrok Support: Now its possible to use Ngrok to scan your local development environment with an Free account.

Feature to disable Automatic consent Optin on Scans.


Version 1.0.9 - Features and Bugfixes
________________

API authorization: To extend Scan limits on Request (Optional)

Extended Extension Settings, see in Extension Settings Dokumentation

Added a Script Blocker, to block scripts from third party services, if not found in Consent (Optional)


Version 1.0.8 - Beta Support for Typo3 v12
-------------

Take your website to the next level with our new support for Typo3 v12.

New feature: added a tertiary button to the consent modal.

Added a Button-role "Hide Button", now its possible to have a all behaviors, Settings Button, Accept All Button, Reject all Button and a Hide Button in the Consent Module.

Added an Backend Language Select for Home Tab to view the current configuration in languages


Version 1.0.7 - Fully Customizable Consent Buttons
-------------
Added a Button-role "Reject all", now its posible to have a Reject All Button, instand of a Settings Button.

Fixed issue with ignoring unknown categories in the backend filter.

Improved behavior: now keeping original iframe width and height if found.

Added script override to RenderUtility.

Improved backend management: reworked CookieSettingsBackendController, removing deprecations.

Improved extension configuration: added cookie-consent revision management.


Version 1.0.6 - Bugfixes
-------------
Added missing block description to the settings modal.

Added switch effect for category expand boxes.

Fixed issue with filter-categories that have no category suggestion.


Version 1.0.5 - Frontend Templates
-------------
New feature: added frontend templates to override the base HTML DOM.

Implemented better iframe management for cookie management.


Version 1.0.4 - Tree organization
-------------
New feature: added a select field for the variables to TCA identifier.

Added a counter for missing variables in the treelist/home view.


Version 1.0.3 - Smarter Backend Management
-------------

Get the most out of the localization with our improved UI list management.

Say goodbye to outdated designs and hello to a sleeker, more user-friendly interface.

Keep your scans organized and efficient with our new basic scan management feature.


Version 1.0.2 - Autoconfiguration Made Easy
-------------

Streamline the setup of your public website with our new Autoconfiguration feature.

Our external Python-based Chromium Scanner classifies services and cookies, and Provides all information per API.


Version 1.0.1 - Data import via Upgrade Wizard
-------------

Speak the language of your choice with our support for multiple languages.

Experience a seamless upgrade process with our new Cookie API.

Importing new data has never been easier with our streamlined Upgrade Wizard and our new Cookie API.


Version 1.0.0 - The Foundation of a Great Extension
-------------

Get started with our basic extension release, the foundation for future updates and improvements.
