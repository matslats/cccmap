<html>
  <head>
    <title>Major community currency networks</title>
    <script src='https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.css' rel='stylesheet' />
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <meta charset="UTF-8">
    <style>/* all this changes the redness of the pin */
        /* Members of CCC should appear brighter */
        .rds{filter: opacity(20%);}
        .ices{filter: hue-rotate(140deg) saturate(0.20%);}
        .tbnsw{filter: hue-rotate(140deg) saturate(0.20%);}
        .hourworld{filter: hue-rotate(200deg) saturate(0.20%);}
        .tbusa{filter: hue-rotate(240deg) saturate(0.20%)}
        .cforge{filter: hue-rotate(10deg) brightness(0.8);}
        .cesoz{filter: hue-rotate(160deg);}
        .cesmain{filter: hue-rotate(120deg);}
        .static{filter: hue-rotate(130deg);}
        .letslinkuk{filter: hue-rotate(140deg);}
    </style>
  </head>
  <body>
    <div id='map' style='width: 90%; height: 800px;'></div>
    <script>// Create the map using tiles
      L.mapbox.accessToken = 'pk.eyJ1IjoibWF0c2xhdHMiLCJhIjoiY2oyeXcxdzdmMDBhNTMyanNmbzN1dGt2cSJ9.XUw8z45hXx8MoXgQ-G5QUw';
      var map = L.mapbox.map('map', 'mapbox.streets');
    </script>

    <!-- Might need to add this to apache: AddType application/x-httpd-php .html -->
    <script>// Add the data layer
      var myLayer = L.mapbox.featureLayer().addTo(map);
      var geoJson = <?php include './geo.json'; ?>
      // Set a custom icon on each marker based on feature properties.
      myLayer.on('layeradd', function(e) {
        var marker = e.layer,
          feature = marker.feature;
        marker.setIcon(L.icon(feature.properties.icon));
      });
      myLayer.setGeoJSON(geoJson);
    </script>

    N.B. Map shows only communities with more than 10 members.
    <!-- todo count communities in different ways -->
  </body>
</html>