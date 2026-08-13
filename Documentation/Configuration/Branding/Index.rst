.. _branding:

========
Branding
========

The cookie banner shows a small notice in its footer::

    Cookie-Banner by CodingFreaks

It links to https://coding-freaks.com/ and is enabled by default.


How to switch it off
--------------------

Set the constant to ``0``. No template override and no code change is required.

TypoScript constants:

.. code-block:: typoscript

    plugin.tx_cfcookiemanager_cookiefrontend.frontend.show_branding = 0

Site settings (TYPO3 v13+), in :file:`config/sites/<identifier>/settings.yaml`:

.. code-block:: yaml

    plugin:
      tx_cfcookiemanager_cookiefrontend:
        frontend:
          show_branding: false

The setting is also listed as :guilabel:`Show Branding` in the
:guilabel:`Site Settings` backend module and in :guilabel:`Constant Editor`
under the :guilabel:`branding` category.

When disabled, the notice is not rendered at all: the element never enters the
DOM. It is not merely hidden with CSS.


Why it exists
-------------

This extension is free and maintained by me alongside other work.
There is no paid tier, no license key and no usage limit.
The notice in the banner is one of the few ways the project gets any visibility at all,
and visibility is what brings contributors and the occasional donation.

If you would rather not have it on your site, switch it off. That is what the
setting is for, and using it does not disable or degrade anything. If the
extension saves you time, support is welcome instead:
https://buymeacoffee.com/codingfreaks