.. include:: ../Includes.txt


=============
Example Services
=============


Microsoft Clarity
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
