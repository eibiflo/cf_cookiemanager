.. include:: ../Includes.txt

=============
Google Consent Mode
=============

- Add Tag Manager to your Website with Coding-Freaks cookie manager Backend module.
    - Open the Backend Module and open the "Analytics" Category.
    - Serach for "Google Tag Manager" in services and add it to the Analytics Category.
    - Save the Category

- Fill in your Variable Provider for your Google TAG ID - or replace it in the `opt_in_code` field.
    - open the "Google Tag Manager" Service (detail-view).
    - Switch to Script Tab.
    - Scroll down to the Variable Provider Section and create a Assignement for your Google Tag Manager ID. (GT_TRACKING_ID)


Add the Consentmode JavaScript to the `opt_in_code` field.

.. code-block:: javascript

  var script1 = document.createElement('script');
  script1.type = 'text/javascript';
  script1.async = true;
  script1.src = 'https://www.googletagmanager.com/gtag/js?id=[##GT_TRACKING_ID##]';
  script1.setAttribute('data-cookiecategory', 'analytics');

  var script2 = document.createElement('script');
  script2.type = 'text/javascript';
  script2.setAttribute('data-cookiecategory', 'analytics');
  script2.innerHTML = `
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    console.log("Header");
    gtag('config', '[##GT_TRACKING_ID##]');

    gtag('consent', 'default', {
      'ad_storage': 'denied',
      'ad_user_data': 'denied',
      'ad_personalization': 'denied',
      'analytics_storage': 'denied'
    });

    gtag('consent', 'update', {
      'ad_storage': 'granted',
      'ad_user_data': 'granted',
      'ad_personalization': 'granted',
      'analytics_storage': 'granted'
    });
  `;

      script1.onload = function() {
        document.head.appendChild(script2);
      };

      document.head.appendChild(script1);

Add Consentmode Update to the `opt_out_code` field.

.. code-block:: javascript

    gtag('consent', 'update', {
        'ad_user_data': 'denied',
        'ad_personalization': 'denied',
        'ad_storage': 'denied',
        'analytics_storage': 'denied'
    });

- Go to your Tag Manager Admin page, to consentmode configuration and press "Test Consentmode (optional)" to check if the Consent update is successful.
    - Open https://tagmanager.google.com/# and select your account or tag to open the admin page.
    - Open the Tag Manager Configuration Page

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_start.png
       :class: with-shadow
       :alt: Cosnentmode Admin
       :width: 100%

    - Select the "Administration" Tab and open the "Consent Mode Configuration" section.

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_administration.png
       :class: with-shadow
       :alt: Consentmode Admin - Administration
       :width: 100%

    - Press the "Test Consentmode (optional)" Button to check if the consent update is successful.

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_debug.png
       :class: with-shadow
       :alt: Consentmode Admin - Debug
       :width: 100%

    - Opt In in the Cookiemanager in your Typo3 Frontend

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_debug_init.png
       :class: with-shadow
       :alt: Consentmode Admin - Debug
       :width: 100%

    - Opt Out in the Cookiemanager in your Typo3 Frontend

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_debug_optin.png
       :class: with-shadow
       :alt: Consentmode Admin - Debug
       :width: 100%

    - Opt In (Again) in the Cookiemanager in your Typo3 Frontend

    .. figure:: ../../../Images/Configuration/ConsentMode/consent_mode_debug_optout.png
       :class: with-shadow
       :alt: Consentmode Admin - Debug
       :width: 100%

