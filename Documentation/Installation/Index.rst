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

This extension provides an external API to automatically create cookie assignments to individual services in various languages.

To do this, go to the Typo3 backend and select the  :guilabel:`Upgrade` module in the Admin Tools tab.
Open the :guilabel:`Upgrade Wizard` and Execute the "Cookiemanager Static Data Update" Task.
The extension automatically configures itself and also creates the corresponding data set and Language overlays from site config.yaml.


You can now Configure the Extension in the :guilabel:`Cookie Settings` module.


.. Tip::

    If there is no task available, look to the "Wizards marked as done" section and select :guilabel:`Mark undone`

.. Tip::
    If the import is unsuccessful:

    Disable the extension via Composer :guilabel:`composer rem codingfreaks/cf-cookiemanager`.

    Remove Mysql-Table fia Typo3 :guilabel:`Database Analyzer`.

    Install Extension again with :guilabel:`composer require codingfreaks/cf-cookiemanager`.






