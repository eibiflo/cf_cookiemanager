.. include:: ../Includes.txt


===========================
Themes and Styling
===========================


The Cookie Manager includes a default Standalone theme, which features a clean and straightforward design that can be easily customized.
Additionally, there are several other themes provided with the Cookie Manager, serving as foundations for creating your own unique theme.


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

Frequently, simple CSS changes may not suffice to meet your requirements.
To address this, the Cookie Manager offers several HTML components that enable you to construct your own theme effectively.

You can locate these components in the following directory: EXT:cf_cookiemanager/Resources/Static/*.html.

* consentmodal.html
* settingsmodal.html
* settingsmodal_category.html


Understanding the IDs within the Javascript Context is crucial.
While you have the freedom to modify the CSS and HTML structure, it's vital to retain the same dom IDs. These IDs are utilized in the consent.js to identify the elements and establish the associated functionality.

Below is a basic example of the Consent Modal. Please note that the Text areas and Buttons are intentionally left empty, as the actual text content is dynamically added by the Javascript.

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


If you prefer to utilize your custom HTML structure, you must make adjustments to the Extension Configuration and specify the path to your HTML file.

To do this, follow these steps:

* Open the Settings Module.
* Navigate to "Configure Extensions."
* Search for "cf_cookiemanager" in the list of extensions.
* Choose the "Template-Tab."
* Set the path to your HTML file.
* EXT:your_sitepackage_or_extension/Resources/Static/consentmodal.html
* EXT:your_sitepackage_or_extension/Resources/Static/settingsmodal.html

By following these steps, you can integrate your own HTML structure into the Cookie Manager extension.

