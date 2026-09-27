.. _data-and-requests:

=========================
Data and network requests
=========================

What the extension loads in the visitor's browser, what your TYPO3 server sends to the
CodingFreaks platform, and when. Every request to the platform goes to the address in
the setting ``end_point`` (see :ref:`extension-settings`). In the tables below,
``{end_point}`` stands for that address.

This page describes what the software does. Whether and how you have to mention it in
your privacy policy is for you to decide.


Frontend: the visitor's browser
-------------------------------

The extension itself makes no request to CodingFreaks or any other third party from the
visitor's browser. Everything it adds is served by your own site:

*   :file:`consent.js`, :file:`iframemanager.js` and :file:`default.css` from the
    extension's public resources.
*   The banner configuration, either inline in the page or as
    :file:`typo3temp/assets/cookieconfig<language><hash>.js`. The field
    :guilabel:`In Line Execution` in the Frontend Settings decides which.
*   The branding notice is a plain link. It loads nothing (see :ref:`branding`).

What loads after consent is what you configure: the scripts and iframes of your
services. One field can load something **before** consent: a service's
:guilabel:`iFrame Thumbnail function or url`. If you fill it, the browser loads that URL
(or runs that function) to show the preview of blocked content, and that can be a third
party host.

The consent cookie (``cookie_name``, default ``cf_cookie``) is set by :file:`consent.js`
in the browser.


.. _data-thumbnails:

Thumbnail API
~~~~~~~~~~~~~

With ``thumbnail_api_enabled`` (default ``true``) and no own thumbnail on the service,
blocked iframes get a preview image:

#.  The browser requests the image from your own site (page type ``1723638651``).
#.  If :file:`typo3temp/assets/cfthumbnails/` holds a copy younger than seven days,
    TYPO3 returns it. Nothing is sent.
#.  Otherwise your **server** sends ``POST {end_point}getThumbnail`` with the URL of the
    embedded content, the width and height, the host name of your site (``domain``), and
    ``scan_api_key`` and ``scan_api_secret`` if they are set. The visitor's browser does
    not talk to the platform.

Switch it off with ``thumbnail_api_enabled = false``. The cache can be cleared in the
:guilabel:`Administration` tab (:guilabel:`Clear Thumbnail Cache`).


.. _data-consent-tracking:

Consent tracking
~~~~~~~~~~~~~~~~

Off by default (``tracking_enabled``). When on, the browser sends one request to your
own site (page type ``1682010733``) after the visitor's first choice in the banner. TYPO3
stores it in the table ``tx_cfcookiemanager_domain_model_tracking``:

*   from the browser: language (``navigator.language``), referrer,
    ``navigator.webdriver``, consent type (``all``, ``necessary`` or ``custom``)
*   added by the server: page ID, user agent header, time

The Dashboard widget reads this table. In 2.x nothing of it is sent to the platform.


Backend: your TYPO3 server
--------------------------

Only the actions below send requests. The requests come from the TYPO3 server, so the
server needs outgoing HTTPS access to ``{end_point}``.

..  list-table::
    :header-rows: 1
    :widths: 30 30 40

    *   - When
        - Request
        - What is sent
    *   - :guilabel:`Start Configuration`, :guilabel:`Install Presets`
        - ``GET {end_point}frontends/{lang}``, ``categories/{lang}``,
          ``services/{lang}``, ``cookie/{lang}``, for every site language
        - The language code in the URL. No key, no secret.
    *   - :guilabel:`Start Configuration`, API step, when you enter key and secret
        - ``POST {end_point}v1/integration/ping``
        - ``api_key``, ``api_secret``, ``platform`` (``typo3``), ``plugin_version``.
          On success the values are saved in the site settings.
    *   - :guilabel:`Start Configuration`, API step, when the site settings already hold
          key and secret
        - ``POST {end_point}v1/integration/ping``
        - The same fields, with the stored key and secret, to the stored endpoint. Nothing
          is saved.
    *   - :guilabel:`Administration`, :guilabel:`Check for Dataset API-Updates`
        - The same four ``GET`` requests as the preset install
        - The language code in the URL. No key, no secret.
    *   - :guilabel:`Administration`, :guilabel:`Check API Integration`
        - ``POST {end_point}v1/integration/ping``, then share-config (below)
        - As above, with the stored key and secret
    *   - :guilabel:`Autoconfiguration & Reports`, start a scan
        - ``POST {end_point}scan``
        - The URL to scan, the page limit, the XPath of the banner's accept button (or
          the flag that disables clicking), ``apiKey`` if set. No secret.
    *   - Opening the :guilabel:`Cookie Settings` module while a scan is waiting or running
        - ``GET {end_point}scan/{identifier}``
        - The scan identifier in the URL
    *   - Importing a scan result, and after :guilabel:`Install Presets`
        - share-config (below)
        - Only when key and secret are set
    *   - Saving or deleting a cookie service, cookie, category or frontend record
        - share-config (below)
        - Only when key and secret are set

**share-config** is ``POST {end_point}v1/integration/share-config`` with ``api_key``,
``api_secret`` (also as header ``x-api-key``) and your banner configuration: the banner
settings (cookie name, revision, lifetime, path, autorun, force consent, layout), and per
language the banner texts, categories, services (name, description, identifier, provider,
category) and cookies (name, regex flag, lifetime, domain, path, secure flag, description,
and the service name linked to its :guilabel:`DSGVO Link` as provider). It
contains no visitor data. The platform uses it to compare its scan results with what your
banner declares.

Nothing is sent by :guilabel:`Offline Configuration`: you download the preset archive in
your browser and upload it into TYPO3.

Links in the backend module open pages in your browser when you click them:
:guilabel:`Open report on the platform` goes to the scan report in your project on the
platform. The address is ``end_point`` without a trailing ``api/`` (an endpoint without
it is used as it is), followed by
``administration/manage/<Project ID>/cmp/scan-show/<scan identifier>``. It needs a login
on the platform and is only shown with key and secret set. Register and tutorial links
go to ``coding-freaks.com``.


What 2.x does not send
----------------------

*   No consent records of your visitors. Consent tracking stays in your database.
*   No usage data or telemetry of the extension, whatever ``allow_data_collection`` says.
*   Nothing on a normal page view, except the thumbnail request described above.
