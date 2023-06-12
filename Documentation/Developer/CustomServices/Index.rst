.. include:: ../Includes.txt


===========================
Custom Service Configuration
===========================



Leaflet & Openstreetmap
======================


First of all Include leaflet and Openstreetmap like you wish in Typo3.

Add the attribute :guilabel:`data-service="leaflet"` to the script.
Add the attribute   :guilabel:`type="text/plain` to the script.
Ensure that the service with the identifier :guilabel:`leaflet` exists and is enabled.

You can now Include the Script in any Place of your HTML Dom.
The Cookie Manager hooks it on Consent accept.
 :guilabel:`<script type="text/plain" data-service="leaflet" src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>`

For an quick an dirty way to test use this code.

.. code-block:: html

      <div
               data-service="leaflet"
               id="makemerandom"
               data-autoscale>
       </div>

       <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin=""/>
       <script type="text/plain"  data-service="leaflet" src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js" integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>


       <script type="text/plain" data-service="leaflet">

           const map = L.map('makemerandom').setView([51.505, -0.09], 13);
              console.log("RUNmap");
           const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
               maxZoom: 19,
               attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
           }).addTo(map);

           const marker = L.marker([51.5, -0.09]).addTo(map)
               .bindPopup('<b>Hello world!</b><br />I am a popup.').openPopup();

           const circle = L.circle([51.508, -0.11], {
               color: 'red',
               fillColor: '#f03',
               fillOpacity: 0.5,
               radius: 500
           }).addTo(map).bindPopup('I am a circle.');

           const polygon = L.polygon([
               [51.509, -0.08],
               [51.503, -0.06],
               [51.51, -0.047]
           ]).addTo(map).bindPopup('I am a polygon.');


           const popup = L.popup()
               .setLatLng([51.513, -0.09])
               .setContent('I am a standalone popup.')
               .openOn(map);

           function onMapClick(e) {
               popup
                   .setLatLng(e.latlng)
                   .setContent(`You clicked the map at ${e.latlng.toString()}`)
                   .openOn(map);
           }

           map.on('click', onMapClick);

       </script>
