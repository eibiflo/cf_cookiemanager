.. include:: ../Includes.txt

.. _changelog:

==========
Change log
==========


Dev-Version 1.9.0 - (Testing) Extension Ready for Typo3 V14
------------

Since Typo3 14 Released, we are preparing the Extension for Stable Support.
It´s already available in the Main-Branch, but we are doing some final tests and fixes.
Feel free to test it, but be aware that this is a Development Version and is not marked as stable for Production use.

[TASK] Core Tests for T14

[TASK] Removed Deprecation #106972 searchFields

[TASK] Removed Deprecation for renderType t3editor to codeEditor

[TASK] Removed Deprecation for PHPUnit 12

[TASK] RenderUtility - Respect Pid for Multi-Site Usage

[TASK] Update-Wizard for Liste-Type Migration

[TASK] Update InstallController to new SiteChanges Logic

[TASK] Move Extension Config to Site-Level to fully Support Site-Sets

[TASK] Respect SiteSet Config and Typoscript Defaults for new cookie_domain Value #68

Version 1.8.3 - Added Dynamic cookie_domain
------------

[TASK] Added dynamic cookie_domain value in Constants and SiteSet Configuration to ensure that the cookie is set for the correct domain, even if the domain changes (e.g. in multi-site setups  www.domain.tld and app.domain.tld).


Version 1.8.2 - URL-Fragments in Thumbnail Generation and Storage UID for Install Controller
------------

[TASK] Added URL-fragments to the Thumbnail Generation to ensure that the correct URL is used for the thumbnail generation, even if the URL contains fragments.

[TASK] Added Storage UID for Install Controller to ensure that the correct storage is used for the new installation process #66.


Version 1.8.1 - Resolved Runtime Deprecation
------------

[TASK] Resolved PHP Runtime Deprecation Creation of dynamic property


Version 1.8.0 - Data Administration
------------

This release introduces Data Administration to the TYPO3 Cookie-backend, enabling full integration of the `Cookie Database <https://coding-freaks.com/cookie-database>`_ to check your local datasets for changes/updates.

[TASK] Added a Thumbnail Cache clear option to Cookie-Administration interface

[TASK] Added Update Wizard for Frontend-Datasets

[TASK] Implemented Ignore Update function for Cookies,Services,Categories and Frontends

[TASK] Added new installation logic API and Offline Support

[TASK] Added API Endpoint Error for Updates

[TASK] Admin Tab only and No Updates Feedback

[TASK] Implemented a InsertService for new Datasets

[TASK] Implemented a ComparisonService and a base route for Insert Logic

[TASK] Implemented a base logic for Database Upgrades and Review process for API and Local changes to keep Database Up-To-Date

[TASK] Respect Storage UID from Frontend Controller in addExternalServiceScripts

[BUGFIX] Check for external scripts as an ObjectStorage() thanks to @MarkusEhrlich

Version 1.7.4 - Bugfixes and Stablization
------------

[BUGFIX] Invalid HTML in Consent-Settings Button #59

[TASK] Check for unknown API Errors in ScanRepository

[BUGFIX] Impress and Data Policy Link colors in Default Theme set to Primary color

[BUGFIX] Remove default value constraints from TEXT fields for MySQL 8.0 compatibility

[BUGFIX] Fixed an issue where starting the configuration for a second pagetree caused an exception due to duplicate mm-table entries for translations from the first pagetree being generated again

[BUGFIX] Table has no field sorting, sort by name #58, thanks to @nlehmkuhl

[BUGFIX] Added missing label for toggle in consent.js to improve accessibility #56, thanks to @nlehmkuhl

[BUGFIX] Prevent duplication of untranslated cookie services


Version 1.7.3 - Features and Stablization
------------

[TASK] Possibility to set cookie service accepted by default #55

[TASK] Mixed type issue #52

[TASK] Removed Dark-Pattern from defaulttheme (Primary,Secondary buttons - same color) thanks to @nlehmkuhl

[BUGFIX] Keyboard improvements for cookie details #50 thanks to @nlehmkuhl (a11y)

[TASK] Added a Documentation for Google Consent Mode integration

[TASK] Remove aria-hidden on Consent Modal Category and Service to make button clickable in iOS VoiceOver mode (a11y)


Version 1.7.2 - Bugfixes and Stablization
________________

[TASK] Support Typo3 Site sets

[TASK] Backend Module and Tours - Darkmode Support

[TASK] TCA Migrations to new standards

[TASK] Resolved Deprecation: #96107 #99586, and #97866

[BUGFIX] Creation of dynamic property $foundProvider is deprecated in Scans #45

[BUGFIX] PHP Warning: Undefined array key "path" CookieFrontendController

[BUGFIX] Add missing link viewhelper thanks to @andyhirsch


Version 1.7.1 - Stablization for Typo3 13
________________

[BUGFIX] Removed Wildcard selector from iframemanager styles #42

[TASK] Migrated MultipleSideBySlide Select Element to new standards

[TASK] Update Functional Wizard-test to init ConfigurationManagerInterface

[TASK] Completed Frontend-Tour todos

[TASK] Stablization for Typo3 13

[TASK] Parse request from controller to ThumbnailService to ensure proper initialization

[TASK] Reorder parameters to resolve deprecation warnings

[BUGFIX] Dead-end in the tutorial #34

[BUGFIX] Increase referrer column length to handle long URLs


Version 1.7.0 - Thumbnail API for Iframe Preview
________________

[TASK] Added Thumbnail API for Iframe Preview, if content is blocked (Uses external Endpoint) can be enabled in Extension Settings


Version 1.6.4 - Bugfixes and Task Updates
________________

[BUGFIX] HelperUtility unknown result warning

[BUGFIX] Typo and documentation URL

[TASK] Changed Tracking-URL embed implementation

[TASK] change default position for open frontend settings icon

Version 1.6.3 - Danish language files
________________

[TASK] Danish language files #35

[TASK] Implemented a SiteWrite for the Functional Testing for Typo3 v13

[TASK] Adding Danish Language to StaticData thanks to @Beltshassar

Version 1.6.2 - Bugfixes on the Iframemanager frontend rendering
________________

[TASK] Set target _blank by default import from API Issue #32

[BUGFIX] Fixed Problems with translations from iframemanager #30


Version 1.6.1 - Removed Debug console.log
________________

[TASK] Removed Debug statements from Frontend Rendering #31


Version 1.6.0 - Additional Frontend Settings
________________

[TASK] Moved some extension configuration settings to site constants

[TASK] Additional Frontend Settings force_consent/autorun_consent #27


Version 1.5.4 - Frontend Cookie Listing
________________

[TASK] Plugin to view a list with all Services and Cookies in the Frontend (GDPR Page #24 )


Version 1.5.3 - Permissions for Cookie Settings Module
________________

[BUGFIX] Backend Module access for Users and Renamed Middleware identifier

Version 1.5.2 - Features
________________

[TASk] Added an option to enable or disable JavaScript obfuscation in tracking code to avoid "eval" function. #23

[TASk] Refactored tour loading

Version 1.5.1 - Removed Dom Parser Dependencies
________________

[TASK] Removed Dom Parser Dependencies, now using Regex and str_replace to replace the GDPR-content.

[TASK] Multiple Languages per API English,German,Spanish,Italian,Portuguese,Polish,Dutch,French, fallback to english, if no free API KEY is used on a later state.



Version 1.5.0 - Typo3 v13 Support
________________

[TASK] Added Typo3 v13 Support

[TASK] Drop Typo3 v11 Support

[TASK] Drop PHP 7.x dependencies

[TASK] Added Composer Requirements for non-Composer installations in Resources/Private/PHP

[TASK] Support PHP 8.3

Version 1.4.4 - Experimental changes (Dynamic Page Content Replacement)
________________

Since we have issues with the DOM Parser, we have to change the parser to a new one, this solves some issues, but we have to test it in the next weeks, because it also changes the behavior of the Original (maybe invalid) DOM.
Experimental changes, ware made, so we don't have to use any DOM Parser by using Regex replacements, this is an solution to keep the Original DOM without any changes, but it's not the best solution, because we have to use Regex to find the right place to replace the content. This can also lead to issues.

[BUGFIX] Respect hidden flag in TCA, fixed issue #21

Version 1.4.3 - Parser Changes and Bugfixes
________________

[BUGFIX Fixed Issue #19 "updateScan" not in Repository

[TASK] Changed DOM Parser to Masterminds/html5-php - Reference to issue #18 #17 and pull request #16

Version 1.4.2 - Optimizations
________________

[TASK] Adjust default condition for displaying Cookie-Manager

[TASK] Added Language Labels for CookieSettingsBackendController

[TASK] Ensures the HTML content is saved as UTF-8.

Merge pull request from jakobwid/remove-html_entity_decode

Version 1.4.1 - Offline Dataset Installation
________________

[TASK] Added offline dataset installation handling when API is unreachable issue (#14)

[TASK] Added short overview of Extension settings in home tab

[BUGFIX] Missing Storages in BasicConfig

[BUGFIX] Adjusted layout, display Button-Text with long Text in Box

Version 1.4.0 - Backend Look & Feel
________________

Overworked the Backend UI for a standard look and feel.

New Donut chart for Consent Tracking, shows accept types

Added a JavaScript Obfuscator for the Tracking.js (Adblock Detection)

Simplified the Installation process.

Fixed Issue #8 Function always returns true

Fixed a bug in the Iframemanager, remove blocked iframes from the dom.

Version 1.3.5 - Bugfixes
________________

Fixed issue #12 - broken feature data-script-blocking-disabled

Fixed issue #13 - empty label rendered in the iframemanager

Version 1.3.4 - Added XSD schema validation
________________

Create default/fixed value nodes during XSD schema validation in RenderUtility


Version 1.3.3 - Bugfixes
________________

Fixed issue #10 - Middleware Hook brok XML from Sitemap.xml (resulted in invalid sitemap.xml)

Added missing tertiary button in default consent.js fallback.


Version 1.3.2 - CodeQuality changes
________________

Added Extension Development Section to Documentation

Added API Repository for API Communication

Frontend and Backend Test Beta with codeception

Fixed Issue #9

Moved Content Override to Middleware

Removed Deprecated contentoverride TCA

Added Cookie tests for Update Wizard (Multi Site Usage)


Version 1.3.1 - Small changes
________________

Removed div[data-service]::before, width and height are set RenderUtility

BUGFIX Unittest fix UNIQUE constraint failed


Version 1.3.0 - New Features
________________

Added impress and data-policy Link for consent modal in backend. (Database updated needed)

Bugfix Possible unknown array key "id" in BackendView

Cookie-Information: Added the cookie information in the Settings-Modal.

Script-Blocker: Added a Fluid-Template for the Script-Blocker.

Cookie-Translation: Added Cookie language overlays for each cookie on a site-root.


Version 1.2.2 - Template Management with Examples
________________

Add Templates and Template Management Documentation for extending the Layout.

Removed Deprecation: mb_convert_encoding() in PHP 8.2

Removed Deprecation: #96972

Removed Deprecation: #100053

Fixed Issue: #5

Fixed a bug in the Cookie API import, now Cookies are preconfigured for all rootpages in sites config.

Version 1.2.1 - Reworked Guided Tours
________________

Reworked the guided tours, removed the old dependencies and bugfixes.

Added Requirejs Module to manage Guided Tours.

Added matomo Service in preconfigured services, old installs need to update the service manually in the Upgrade Wizard.


Version 1.2.0 - Backend UI Improvements and Guided Tours beta
________________

Added beta guided tours to the cookiemanager backend module, to help you get started with the extension.

Improved the backend home UI, to make it more user friendly.

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
