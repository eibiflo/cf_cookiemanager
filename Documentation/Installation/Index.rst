.. include:: ../Includes.txt

.. _installation:

============
Installation
============

To use the CodingFreaks cookie plugin in Typo3, you must first install it in your Typo3 system.
This can be done by using Composer.

.. code-block:: bash

   composer require codingfreaks/cf-cookiemanager


Include TypoScript template
===========================

To properly use the plugin, it is necessary to include the basic TypoScript provided by the extension.
To do this, go to the :guilabel:`Web > Template` module in the Typo3 backend, select the root page, and switch to the
:guilabel:`Info/Modify` view.

From there, click on :guilabel:`Edit the whole template record`  and switch to the Includes tab.
Next, add the :guilabel:`CodingFreaks Cookie Manager (cf_cookiemanager)` template from the list on the right.


Autoimport Datasets for Languages
===========================


To facilitate the one-time setup, this extension provides an external API to automatically create cookie assignments to individual services in various languages.

To do this, go to the Typo3 backend and select the  :guilabel:`Cookie Settings` extension in the Web module.
The extension automatically configures itself and also creates the corresponding data set overlays.

For example, sys_language_uid is taken from the sites :guilabel:`config.yaml` and imported."




.. Tip::
    If the import is unsuccessful:

    Disable the extension via Composer :guilabel:`composer rem codingfreaks/cf-cookiemanager`.

    Remove Mysql-Table fia Typo3 :guilabel:`Database Analyzer`.

    Install Extension again with :guilabel:`composer require codingfreaks/cf-cookiemanager`.