.. include:: ../Includes.txt

.. _installation:

============
Installation
============

In order for you to use our cookie plugin in Typo3, you must install it in your Typo3 system.
To do this, you must first install the plugin with Composer.

.. code-block:: bash

   composer require codingfreaks/cf-cookiemanager


Include TypoScript template
===========================

It is necessary to include at least the basic TypoScript provided by this
extension.

Go module :guilabel:`Web > Template` and chose your root page. It should
already contain a TypoScript template record. Switch to view
:guilabel:`Info/Modify` and click on :guilabel:`Edit the whole template record`.

Switch to tab :guilabel:`Includes` and add the following templates from the list
to the right: :guilabel:`CodingFreaks Cookie Manager (cf_cookiemanager)`.

Read more about possible configurations via TypoScript in the
:ref:`Reference <typoscript>` section.


Autoimport Datasets for Languages
===========================


To facilitate the one-time setup, this extension provides an external API to automatically create cookie assignments to individual services in various languages.

To do this, go to the Typo3 backend and select the  :guilabel:`Cookie Settings` extension in the Web module.
The extension automatically configures itself and also creates the corresponding data set overlays.

For example, sys_language_uid is taken from the sites :guilabel:`config.yaml` and imported."




.. Tip::
    If the import is faulty:

    Disable the extension via Composer :guilabel:`composer rem codingfreaks/cf-cookiemanager`.

    Remove Mysql-Table fia Typo3 :guilabel:`Database Analyzer`.

    Install Extension again with :guilabel:`composer require codingfreaks/cf-cookiemanager`.