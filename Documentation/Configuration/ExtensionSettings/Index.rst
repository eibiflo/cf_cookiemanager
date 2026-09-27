.. _extension-settings:

==================
Extension Settings
==================

The extension reads its settings per site. With the site set
``CodingFreaks/cf-cookiemanager`` (TYPO3 v13+) they are site settings. Without the
site set, the extension falls back to the TypoScript constants of the root page.

Where to change them
--------------------

*   Backend: :guilabel:`Site Management > Settings`, open your site, then the category
    :guilabel:`CodingFreaks CookieManager` (:guilabel:`Settings` and :guilabel:`Branding`).
*   File: :file:`config/sites/<identifier>/settings.yaml`. This is the file the backend
    editor writes, and the one to put under version control.
*   Legacy (no site set): TypoScript constants, :guilabel:`Constant Editor` of the root
    template.

All keys share the prefix ``plugin.tx_cfcookiemanager_cookiefrontend.frontend.``. The
table lists the key without it. The source of truth is
:file:`Configuration/Sets/CfCookiemanager/settings.definitions.yaml`.


All settings
------------

..  list-table::
    :header-rows: 1
    :widths: 22 8 22 48

    *   - Key
        - Type
        - Default
        - What it does
    *   - ``disable_plugin``
        - bool
        - ``false``
        - Switches the extension off in the frontend: no banner, and the middleware leaves
          the HTML untouched.
    *   - ``autorun_consent``
        - bool
        - ``true``
        - Opens the consent banner on page load. Has no effect on the blocking of iframes.
    *   - ``no_autorun_on_legal``
        - bool
        - ``true``
        - Does not open the banner on page load when the current page is the imprint or
          data policy page configured in the Frontend Settings.
    *   - ``force_consent``
        - bool
        - ``true``
        - Blocks page navigation until the visitor has made a choice. Needs
          ``autorun_consent``.
    *   - ``hide_from_bots``
        - bool
        - ``true``
        - Does not run the banner when the user agent looks like a bot, crawler or
          webdriver.
    *   - ``cookie_name``
        - string
        - ``cf_cookie``
        - Name of the cookie that stores the visitor's choice.
    *   - ``cookie_domain``
        - string
        - (empty)
        - Domain of the consent cookie. Empty means ``window.location.hostname``.
    *   - ``cookie_path``
        - string
        - ``/``
        - Path of the consent cookie.
    *   - ``cookie_expiration``
        - string
        - ``365``
        - Lifetime of the consent cookie in days.
    *   - ``revision_version``
        - string
        - ``1``
        - Consent revision. Changing it asks every visitor for consent again.
    *   - ``tracking_enabled``
        - bool
        - ``false``
        - Records the visitor's first choice in the banner in a local table. See
          :ref:`data-consent-tracking`.
    *   - ``tracking_obfuscate``
        - bool
        - ``false``
        - Obfuscates the tracking script so that ad blockers do not drop it. The
          obfuscated script uses ``eval()``.
    *   - ``scan_api_key``
        - string
        - (empty)
        - :guilabel:`Project ID (API key)`. Identifies your project on the CodingFreaks
          platform. It is rendered into the page source as an attribute of the banner
          script. See :ref:`introduction-platform`.
    *   - ``scan_api_secret``
        - string
        - (empty)
        - :guilabel:`API secret`. Authenticates this installation against your project.
          Never rendered into the frontend. Treat it like a password, see
          :ref:`extension-settings-secret`.
    *   - ``end_point``
        - string
        - ``https://app.coding-freaks.com/api/``
        - :guilabel:`Platform endpoint`. Base address of every request the backend sends
          to the platform, with a trailing slash. See :ref:`data-and-requests`.
    *   - ``thumbnail_api_enabled``
        - bool
        - ``true``
        - Shows a preview image on blocked iframes. The platform generates the image,
          your server requests it and caches it in :file:`typo3temp/assets/cfthumbnails/`.
          See :ref:`data-thumbnails`.
    *   - ``script_blocking``
        - bool
        - ``false``
        - :guilabel:`Block scripts and iframes that are not assigned to a service`.
          Blocks scripts with ``src`` (from any host, your own included) and iframes from
          other hosts that match no service. They never load, consent does not release
          them: a script gets ``type="text/plain"`` and ``data-service="unknown"``, an
          iframe is replaced by the :file:`scriptblocker.html` notice without a load
          button. This includes your site's own JavaScript files. Add
          ``data-script-blocking-disabled="true"`` to a tag to exempt it. The setup
          wizard offers it as a checkbox, off by default.
    *   - ``allow_data_collection``
        - bool
        - ``false``
        - Stores the answer to step 1 of :guilabel:`Start Configuration` (usage data).
          No code in this version reads it, and the extension sends no usage data.
    *   - ``show_branding``
        - bool
        - ``true``
        - Shows the "Cookie-Banner by CodingFreaks" notice in the banner footer. See
          :ref:`branding`.
    *   - ``cf_consentmodal_template``
        - string
        - ``EXT:cf_cookiemanager/Resources/Static/consentmodal.html``
        - Template of the consent modal. See :doc:`/Developer/Themes/Index`.
    *   - ``cf_settingsmodal_template``
        - string
        - ``EXT:cf_cookiemanager/Resources/Static/settingsmodal.html``
        - Template of the settings modal.
    *   - ``cf_settingsmodal_category_template``
        - string
        - ``EXT:cf_cookiemanager/Resources/Static/settingsmodal_category.html``
        - Template of one category item in the settings modal.

.. note::

    Two backend actions write settings for you. :guilabel:`Install Presets` in
    :guilabel:`Start Configuration` writes ``allow_data_collection``, and
    ``script_blocking`` once the import has succeeded (as TypoScript constants on sites
    without the site set). The API step of :guilabel:`Start Configuration` writes
    ``scan_api_key`` and ``scan_api_secret`` and sets ``thumbnail_api_enabled`` to
    ``true``, once the platform accepts newly entered values. If the site settings
    already hold key and secret, the step only checks them against the stored endpoint
    and writes nothing. A value that is already in place is not written again.


.. _extension-settings-versioned:

Connection settings under version control
-----------------------------------------

Three keys connect the extension to the platform. Keep the secret out of the
repository with an environment variable:

..  code-block:: yaml
    :caption: config/sites/<identifier>/settings.yaml

    plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_key: 'your-project-id'
    plugin.tx_cfcookiemanager_cookiefrontend.frontend.scan_api_secret: '%env(CF_COOKIEMANAGER_API_SECRET)%'
    plugin.tx_cfcookiemanager_cookiefrontend.frontend.end_point: 'https://app.coding-freaks.com/api/'

The nested form (``plugin:`` / ``tx_cfcookiemanager_cookiefrontend:`` / ``frontend:``)
works as well. The flat form above is what the TYPO3 settings editor writes.

Set ``CF_COOKIEMANAGER_API_SECRET`` in the environment of the PHP process (web server,
PHP-FPM pool, container, or a ``.env`` loader). Then clear all caches.


.. _extension-settings-secret:

How TYPO3 treats the placeholder
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Checked against TYPO3 13.4:

*   Reading works. TYPO3 loads :file:`settings.yaml` with placeholder processing, so
    ``%env(...)%`` resolves to the value of the variable.
*   The resolved value is cached with the site settings in the core cache
    (:file:`var/cache/code/core/`). After changing the variable, clear all caches.
*   **Writes by the extension keep the placeholder.** The extension merges its values
    into :file:`settings.yaml` as written, without resolving placeholders, and changes
    only its own keys.
*   **The TYPO3 settings editor replaces it.** Saving in
    :guilabel:`Site Management > Settings` loads :file:`settings.yaml` with the
    placeholders already resolved and writes the whole file back. The secret then stands
    in the file in plain text.

So, when you use the placeholder:

#.  Add the three keys to :file:`settings.yaml` by hand and commit the file.
#.  In :guilabel:`Start Configuration`, the API step then only checks the stored values.
    Do not type key and secret there: typed values are saved as plain text.
#.  Test the connection any time with :guilabel:`Check API Integration` in the
    :guilabel:`Administration` tab. It only reads the settings.
#.  After a save in :guilabel:`Site Management > Settings`, check :file:`settings.yaml`
    with ``git diff`` before you commit.
