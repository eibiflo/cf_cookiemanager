
.. _known-problems:

==============
Known Problems
==============

.. contents::
   :local:
   :depth: 1


Two banners: the theme ships its own cookie consent
---------------------------------------------------

Some site packages bring their own consent banner. Visitors then see two banners, and the
theme's banner does not block anything this extension manages.

**Bootstrap Package**: the site set :guilabel:`Bootstrap Package: Full Package` includes
the set ``bootstrap-package/cookie-consent``, and its setting
``page.theme.cookieconsent.enable`` is on by default. Switch it off in
:guilabel:`Site Management > Settings` (:guilabel:`Bootstrap Package > Cookie Consent >
Enable Cookie Consent`), or in :file:`settings.yaml`:

..  code-block:: yaml
    :caption: config/sites/<identifier>/settings.yaml

    page.theme.cookieconsent.enable: false

Then flush the frontend caches. Pages already in the page cache keep the second banner
until they are rendered again.

The :guilabel:`Cookie Settings` module warns with :guilabel:`Two cookie banners` when this
site setting is on. It reads only site settings. If your site includes the Bootstrap
Package as a static TypoScript template instead of a site set, there is no warning:
switch off the constant of the same name in the Constant Editor.

Other themes: look for a setting named cookie consent, cookie notice or cookie banner.

The same goes for scripts a theme adds itself, for example a Google Tag Manager snippet.
The extension blocks an external script only if it matches a configured service, or if
``script_blocking`` is on. Inline scripts are not blocked.


Static file cache, reverse proxy, CDN
-------------------------------------

The extension works inside TYPO3:

*   The banner and its configuration are rendered with the page and stored in the TYPO3
    page cache.
*   The PSR-15 middleware ``codingfreaks/cf-cookiemanager/gdprhook``
    (:file:`Classes/Middleware/ModifyHtmlContent.php`) rewrites the HTML of every
    response on its way out: script tags and iframes of known services become
    ``type="text/plain"`` or a placeholder until consent.

A layer that answers **without passing TYPO3** (a static file cache, Varnish, a CDN or
edge cache) serves the HTML it stored. If it stored the page before the extension was
active, visitors get no banner and unblocked scripts. If it stored it before your last
change, visitors get the old banner configuration.

What to do:

*   Flush that cache after installing the extension and after every change in
    :guilabel:`Cookie Settings`.
*   Check a cached page in the page source: it must contain :file:`consent.js`, and the
    script tags of your services must carry ``type="text/plain"`` and ``data-service``.
*   The server never reads the consent cookie. :file:`consent.js` evaluates the choice in
    the browser, so the same cached HTML is right for every visitor and the cache does
    not need to vary by that cookie.

Inside TYPO3, saving a record in :guilabel:`Cookie Settings` clears the page cache only
for the page the record is stored on. Other pages keep the cached banner configuration
until you clear the frontend caches. To do that on every save, add to the page TSconfig
of the root page:

..  code-block:: typoscript

    TCEMAIN.clearCacheCmd = pages

With :guilabel:`In Line Execution` off, the configuration is a file in
:file:`typo3temp/assets/`. It is written when TYPO3 renders the page, not on a cache hit.
If a deployment empties :file:`typo3temp/assets/` but keeps the page cache, cached pages
point to a missing file and show no banner. Clear all caches after such a deployment.


Content Security Policy
-----------------------

The extension registers no CSP directives and adds no nonces to the scripts it outputs.
TYPO3 13.4 enforces a frontend CSP only when you enable the feature
``security.frontend.enforceContentSecurityPolicy``. With a CSP in place, check:

*   **Banner configuration**: with :guilabel:`In Line Execution` on, it is an inline
    script without nonce. Switch the field off in the Frontend Settings. The
    configuration then loads as a file from :file:`typo3temp/assets/`, which ``'self'``
    covers.
*   **Opt-in and opt-out code** you enter in a service runs as inline script after
    consent. It needs ``'unsafe-inline'`` in ``script-src``, or move the code into an
    external script file of the service.
*   **tracking_obfuscate** uses ``eval()`` and needs ``'unsafe-eval'``. Leave it off
    under a strict CSP.
*   **Placeholders of blocked iframes** carry an inline ``style`` attribute with the
    iframe's width and height. Without ``'unsafe-inline'`` in ``style-src`` they lose
    that size.
*   **Thumbnails** are fetched from your own site and shown as a ``blob:`` URL. Allow
    ``'self'`` in ``connect-src`` and ``blob:`` in ``img-src``.
*   **Your services** need their own hosts in ``script-src``, ``frame-src``, ``img-src``
    and so on, as after any other embed.

Test with ``security.frontend.reportContentSecurityPolicy`` (report only) first.


Services assigned on the platform do not appear in TYPO3
--------------------------------------------------------

Version 2.x keeps the configuration in TYPO3. The platform does not write into your
TYPO3 installation. After a scan, import the result in TYPO3 under
:guilabel:`Autoconfiguration & Reports`. Services added or assigned on the platform only
reach your banner through that import, or when you add them in :guilabel:`Cookie Settings`
by hand. See :ref:`introduction-platform`.


The TYPO3 settings editor replaces %env()% placeholders
-------------------------------------------------------

Saving in :guilabel:`Site Management > Settings` writes :file:`settings.yaml` with
resolved values. A secret kept in ``%env(...)%`` then stands in the file in plain text.
This is TYPO3 core behaviour. Writes by the extension itself keep the placeholder. See
:ref:`extension-settings-secret`.


No CLI commands
---------------

Presets, the connection check, scans and the import run only from the backend module.
2.x has no console commands for them, so they cannot run in a deployment script.
