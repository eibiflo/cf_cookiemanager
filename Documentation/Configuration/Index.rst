


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

   AutoConfiguration/Index
   CookieCategories/Index
   CookieServices/Index
   ExtensionSettings/Index
   FrontendSettings/Index
