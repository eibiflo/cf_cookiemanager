.. include:: ../Includes.txt

.. _developer:

================
Developer Corner
================

Get some insights on how to customize the plugin and make the most out of it.

.. _developer-api:


.. toctree::
   :maxdepth: 5
   :titlesonly:

   CustomServices/Index
   JavascriptAPI/Index
   EventDispatcher/Index
   Themes/Index
   ExtensionDevelopment/Index


Available data-cc actions
------------------------

Any button (or link) can use the custom ``data-cc`` attribute to perform a few actions without manually invoking the API methods.

Valid values:

- ``c-settings``: show settings modal
- ``accept-all``: accept all categories
- ``accept-necessary``: accept only categories marked as necessary/readonly (reject all)
- ``accept-custom``: accept currently selected categories inside the settings modal

Examples:

.. code-block:: html

    <button type="button" data-cc="c-settings">Show cookie settings</button>
    <button type="button" data-cc="accept-all">Accept all cookies</button>



All configuration options
-------------------------


.. code-block:: rst

    +------------------------+------------+---------+---------------------------------------------------------------------+
    | Option                 | Type       | Default | Description                                                         |
    +========================+============+=========+=====================================================================+
    | autorun                | boolean    | true    | If enabled, show the cookie consent as soon as possible             |
    |                        |            |         | (otherwise you need to manually call the .show() method)            |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | delay                  | number     | 0       | Number of milliseconds before showing the consent-modal             |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | mode                   | string     | 'opt-in'| Accepted values:                                                    |
    |                        |            |         | - opt-in: scripts will not run unless consent is given              |
    |                        |            |         |   (GDPR compliant)                                                  |
    |                        |            |         | - opt-out: scripts - that have categories set as enabled by default |
    |                        |            |         |   - will run without consent, until an explicit choice is made      |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | cookie_expiration      | number     | 182     | Number of days before the cookie expires                            |
    |                        |            |         | (182 days = 6 months)                                               |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | cookie_path            | string     | "/"     | Path where the cookie will be set                                   |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | cookie_domain          | string     | location| Specify your domain (will be grabbed by default)                    |
    |                        |            | .hostname| or a subdomain                                                     |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | cookie_same_site       | string     | "Lax"   | SameSite attribute                                                  |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | use_rfc_cookie         | boolean    | false   | Enable if you want the value of the cookie to be RFC compliant      |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | force_consent          | boolean    | false   | Enable if you want to block page navigation until user action       |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | revision               | number     | 0       | Specify this option to enable revisions. Check below for a proper   |
    |                        |            |         | usage                                                               |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | autoclear_cookies      | boolean    | false   | Enable if you want to automatically delete cookies when user        |
    |                        |            |         | opts-out of a specific category inside cookie settings              |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | page_scripts           | boolean    | false   | Enable if you want to easily manage existing <script> tags.         |
    |                        |            |         | Check manage third-party scripts                                    |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | remove_cookie_tables   | boolean    | false   | Enable if you want to remove the HTML cookie tables                 |
    |                        |            |         | (but still want to make use of autoclear_cookies)                   |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | hide_from_bots         | boolean    | false   | Enable if you don't want the plugin to run when a                   |
    |                        |            |         | bot/crawler/webdriver is detected                                   |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | gui_options            | object     | -       | Customization option which allows to choose layout, position        |
    |                        |            |         | and transition. Check layout options & customization                |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | onAccept               | function   | -       | Method run on:                                                      |
    |                        |            |         | 1. the moment the cookie consent is accepted                        |
    |                        |            |         | 2. after each page load (if cookie consent has already been accepted)|
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | onChange               | function   | -       | Method run whenever preferences are modified                        |
    |                        |            |         | (and only if cookie consent has already been accepted)              |
    +------------------------+------------+---------+---------------------------------------------------------------------+
    | onFirstAction          | function   | -       | Method run only once when the user makes the initial choice         |
    |                        |            |         | (accept/reject)                                                     |
    +------------------------+------------+---------+---------------------------------------------------------------------+
