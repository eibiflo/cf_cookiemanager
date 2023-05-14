.. include:: ../Includes.txt


=============
Example Services
=============


Services in Page Typoscript
================

To implement third-party services in the Page Typoscript using the cookie manager, you can follow these steps:

Define the headerData section in your page TypoScript configuration file. This can be done using the PAGE object and the headerData property, similar to the example below.

- Add the third-party service code to the headerData section using the PAGE object.
  The key here is to use the "type" attribute with a value of "text/plain".
  This tells the browser to only load the script if consent is given by the user.
  You can add as many third-party services as you need by creating additional headerData TEXT objects.

- Now Configure the cookie manager extension to load the third-party scripts only when the user gives consent.
  This can usually be done by adding the data-service attribute to the the scrip tag, (witch is the identifier from the Service in the Database)

Here's an example of how you can add a third-party service for Google Analytics and Microsoft Clarity using the headerData in the PAGE object:

    ..  code-block:: typoscript
        :caption: Example: EXT:your_sitepackage/Configuration/TypoScript/page.typoscript

         page = PAGE
         page {
           headerData {
                 999 = TEXT
                 999.value (
                     <!-- Google Tagmanager -->
                     <script async type="text/plain" data-service="googleanalytics" src="https://www.googletagmanager.com/gtag/js?id=G-XXXXXXXXX"></script>
                     <script type="text/plain" data-service="googleanalytics">
                       window.dataLayer = window.dataLayer || [];
                       function gtag(){dataLayer.push(arguments);}
                       gtag('js', new Date());
                       gtag('config', 'G-XXXXXXXXX');
                     </script>
                     <!-- Google Tagmanager End -->
                 )

                 888.value (
                     <!-- Clarity  -->
                     <script  data-service="clarity" type="text/plain">
                         (function(c,l,a,r,i,t,y){
                             c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
                             t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
                             y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
                         })(window, document, "clarity", "script", "XXXXXXXXX");
                     </script>
                     <!-- Clarity  End -->
                 )

             }
         }


That's it!

By following these steps, you can add third-party services to your TYPO3 extension while ensuring that you comply with data privacy regulations and respect the user's choices regarding cookies.






Example Service from Database (No Coding)
================


Microsoft Clarity
-------------------

Create a new Service in the Typo3 backend like Example: (Microsoft Clarity)

Copy the Clarity Code into :guilabel:`opt_in_code`

.. code-block:: javascript
   :linenos:


    (function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "[##clarityTagID##]");

This code is used to Load the Clarity Script only when the user gives consent for this Service or Category.

Create a  Variable provider
-------------

The placeholder in the code above [##clarityTagID##] is replaced by the value of the variable provider.

Create an Variable for the Placeholder, or directly insert the value in the code.

- :guilabel:`name`   Custom Label name (ex. Clarity ID)
- :guilabel:`identifier`  clarityTagID
- :guilabel:`value` Insert your  Clarity Tag ID
