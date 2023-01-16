.. include:: ../Includes.txt


=============
Cookie Services
=============

A cookie service allows for the management of scripts, cookies, and iframes.

For example: :guilabel:`Thirdparty Scripts and Iframe Manager`.

.. figure:: ../../Images/Ui/backend_servicedetailview.png
   :class: with-shadow
   :alt: Backend Consentmodal
   :width: 100%


Global Settings
===============


- :guilabel:`name` Display Name in Frontend
- :guilabel:`identifier` System Identifier (do not change)
- :guilabel:`description` Service Description
- :guilabel:`provider` Provider URLS seperated by ","
- :guilabel:`dsgvo_link` Link to Service AGB's
- :guilabel:`category_suggestion` Used for simple MM Selection (TODO)


Iframe Manager
===============

The iframe manager is responsible for blocking third-party content.

In the field,   :guilabel:`iframe_notice` text is stored that the user sees when the content is blocked.

-  :guilabel:`iframe_load_btn` Loads the Content of current Div
-  :guilabel:`iframe_load_all_btn` Loads all Content from same Service on same site


Advanced Iframe Configuration
===============

:guilabel:`iframe_thumbnail_url` set valid url for automatic thumbnails or use a Javascript function

:guilabel:`iframe_embed_url` is called on Successful Accept can also use a Javascript function


Example Iframe Thumbnail function

.. code-block:: javascript
   :linenos:


    function(id, callback){
      let parts = id.split("/");
      let videoId = parts[parts.length - 1];
      let videoIdParts = videoId.split("?");
      let videoIds = videoIdParts[0];
      var url = "https://vimeo.com/api/v2/video/"+videoIds +".json";
      var xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
        if (this.readyState == 4 && this.status == 200) {
          var src = JSON.parse(this.response)[0].thumbnail_large;
          callback(src);
        }
      };
      xhttp.open("GET", url, true);
      xhttp.send();
    }


Script Configuration
===============


TODO:
- external_scripts
- variable_priovider
- Example Google Analytics