.. include:: ../Includes.txt


=============
Example Services
=============


Services in Page Typoscript
================

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

Example Service in Optincode: Microsoft Clarity
================

Create a new Service (Microsoft Clarity)

Copy the Clarity Code into :guilabel:`opt_in_code`

.. code-block:: javascript
   :linenos:


    (function(c,l,a,r,i,t,y){
    c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
    t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
    y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
    })(window, document, "clarity", "script", "[##clarityTagID##]");



Create a  Variable provider
-------------

- :guilabel:`name`   Custom Lable name (Clarity ID)
- :guilabel:`identifier`  clarityTagID
- :guilabel:`value` Insert your  Clarity Tag ID
