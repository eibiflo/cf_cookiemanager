.. include:: ../Includes.txt


===========================
Themes and Styling
===========================

The Cookie Manager comes with a default Standalone theme.
The default theme is a clean and simple theme that is easy to customize.

The Cookie Manager also comes with a few other themes that you can use as a starting point for your own theme.

..  code-block:: typoscript
    :caption: Examples found in: EXT:cf_cookiemanager/Resources/Public/Scss/themes/*.css

     page = PAGE
     page {
        #Default Theme Loaded by Extension by default
        includeCSS.cookieconsent = EXT:cf_cookiemanager/Resources/Public/Scss/default.css

        #Standalone Theme Overrides:

        #Clean Theme Example
        #includeCSS.cleanTheme = EXT:cf_cookiemanager/Resources/Public/Scss/theme/clean.css

        #Funky Light Theme Example
        #includeCSS.funkylight = EXT:cf_cookiemanager/Resources/Public/Scss/theme/funkylight.css

        #Darkmode Example
        #includeCSS.darkTheme = EXT:cf_cookiemanager/Resources/Public/Scss/theme/darkmode.css

        #Font Override Only
        #includeCSS.fontoverride = EXT:cf_cookiemanager/Resources/Public/Scss/theme/example.css
     }


Components
-------------------------

Often you need more as just simple css changes. For this reason the Cookie Manager comes with a few HTML components that you can use to build your own theme.

Found in: packages/cf_cookiemanager/Resources/Static/*

* consentmodal.html
* settingsmodal.html
* settingsmodal_category.html

Its important to understand the IDs in the Javascript Context.
Leave the dom ID identical, you can change the CSS and HTML Structure, but keep in mind, that the ids are used in the consent.js to identify the elements, and create the functionality on top of it.

This is a Basic example form the Consent Modal, you can see the Text areas and Buttons are Empty, this is because the Text is added by the Javascript.

..  code-block:: html
    :caption: Example: EXT:cf_cookiemanager/Resources/Static/consentmodal.html

    <div id="cm" role="dialog" aria-modal="true" aria-hidden="false" aria-labelledby="c-ttl" aria-describedby="c-txt" style="visibility: hidden;">
        <div id="c-inr">
            <div id="c-inr-i">
                <div id="c-ttl" role="heading" aria-level="2"></div>
                <div id="c-txt"></div>
            </div>
            <div id="c-bns">
                <button type="button" id="c-p-bn" class="c-bn"></button>
                <button type="button" id="c-s-bn" class="c-bn c_link"></button>
                <button type="button" id="c-t-bn" class="c-bn c_settings"></button>
            </div>
        </div>
    </div>

If you want to use your own HTML Structure, you need to edit the Extension Configuration and set the path to your HTML file.

Open the Settings Module -> Configure Extensions -> serch for cf_cookiemanager -> Open the settings and select the Template-Tab, set the path to your HTML file.

* EXT:your_sitepackage_or_extension/Resources/Static/consentmodal.html
* EXT:your_sitepackage_or_extension/Resources/Static/settingsmodal.html