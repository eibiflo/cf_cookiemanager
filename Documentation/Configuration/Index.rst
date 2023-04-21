


.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

In general, it is easiest to edit the extension in the backend in the :guilabel:`Cookie Settings` module.

What you should also consider is that you create the settings for the cookie categories and services in the respective language. This means that if you have a multilingual website, you must create the settings for each language.

.. figure:: ../Images/Ui/backend.png
   :class: with-shadow
   :alt: Backend
   :width: 100%



Auto-Configuration
-----------------------------

This extension is able to automatically configure the cookie categories and services for you.

This is done by using the :ref:`CF-Cookie-API <cookieapi.coding-freaks.com>` service. You can find the configuration in the :guilabel:`Cookie Settings` module in the backend.

Please note that the output of this tool may not be completely accurate. It is intended to assist with analyzing a website's cookie behavior and should not be relied on as the sole source of information.


Scanning & importing
-----------------------------

By clicking on the `Scan button`, an external web scanner is called up, which analyzes the website and outputs the the Scan Report table.

The Cookie consent ins accepted by default to find all cookies.

If you want to scan the website without accepting the cookie consent, or use custom settings to assist you by finding Services,  you can use the public scanner on `https://cookieapi.coding-freaks.com/` or the API (currently not documented).

These can be imported using the Import button after a successful scan and then edited as required.

Existing services will be ignored and only new services will be added if they are detected.

:guilabel:`Important:` Unknown Services are ignored, and needed to be added manually.

This can be found on the Report page, by clicking on the :guilabel:`Open Report` button.

Unknown Services have no Identifier and you need to set the Provider in the Cookie Service manually.

As example the Scanner or your found an Service like:

- Provider: `grimming.panomax.com/`.
- Cookie: `_gat_panomax`

The service is unknown,  add it now by adding a new Cookie Service in the backend.

The Provider field is used to compare the original URL with the URL from the embedded iframe or script.

You can separate different providers by using a comma. :guilabel:`grimming.panomax.com/,roma.panomax.com` or simply use the domain name :guilabel:`.panomax.com` to match all subdomains.



.. figure:: ../Images/Ui/backend_cookie_service_provider.png
   :class: with-shadow
   :alt: Scan
   :width: 100%



Tracking
--------

If you want to know how many outouts/optins the Cookie Consent has, you can enable the tracking.

This can be done by enabling the tracking in the :guilabel:`Extension Configuration` in the settings module from typo3, by clicking `Enable Cookie Consent Tracking`.

If active the first Action of the Visitor, in the Consent Modal is tracked before any external Javascript is loaded.

The tracking is done by a simple Ajax call to the backend controller, and dose not store any personal data.

You can see the statistics by using the Typo3 Dashboard Module and add the Cookie Consent Widget to the Dashboard.




Table of contents.
=================

.. toctree::
   :maxdepth: 5
   :titlesonly:

   FrontendSettings/Index
   CookieCategories/Index
   CookieServices/Index

