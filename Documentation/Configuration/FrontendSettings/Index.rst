.. include:: ../Includes.txt


=============
Cookie Frontend Settings
=============

In the "Cookie Frontend Settings" tab, settings for the frontend are stored.

For example: :guilabel:`Frontend Consent Modal, Frontend Settings Modal, Frontend Iframe Managers`.


.. _configuration-consent-modal:


Frontend Consent Modal:
======================

The consent modal is the first Modal that the user sees.

From here, they can proceed to advanced settings or directly accept all services.

The layout of the box can be influenced through :guilabel:`layout_consent_modal,transition_consent_modal,position_consent_modal` in the backend.


.. figure:: ../../Images/Ui/backend_consentmodal.png
   :class: with-shadow
   :alt: Backend Consentmodal
   :width: 100%




Default Frontend Preview:
---------------------


.. figure:: ../../Images/Ui/frontend_consentmodal.png
   :class: with-shadow
   :alt: Frontend Consentmodal
   :width: 300px




.. _configuration-settings-modal:

Frontend Settings Modal
---------------------

The settings modal is the detailed view of individual services and cookie groups.

Here, static texts can be replaced.

.. figure:: ../../Images/Ui/backend_settingsmodal.png
   :class: with-shadow
   :alt: Frontend Settingsmodal
   :width: 100%

.. figure:: ../../Images/cookie_settings.png
   :class: with-shadow
   :alt: Introduction Package
   :width: 100%


Customize Frontend Section
---------------------

The cookie manager offers a field in the frontend settings to override the consent button.
Here, HTML can be inserted to replace the static button, which can then be styled dynamically.

Additionally, there is an option to run the frontend javascript as inline javascript.
This can be changed as desired with :guilabel:`in_line_execution` checkbox.