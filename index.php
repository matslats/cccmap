<html>
  <head>
    <title>Major community currency networks</title>
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.css' rel='stylesheet' />
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <meta charset="UTF-8">
    <style>
        th {background-color: #dedede;}
    </style>
  </head>
  <body>
    Zoom in with your mouse to see more sites.
    <div id='map' style='width: 90%; height: 800px;'></div>
    <script>
      mapboxgl.accessToken = 'pk.eyJ1IjoibWF0c2xhdHMiLCJhIjoiY2oyeXcxdzdmMDBhNTMyanNmbzN1dGt2cSJ9.XUw8z45hXx8MoXgQ-G5QUw';
      var map = new mapboxgl.Map({
        container: 'map',
        style: 'mapbox://styles/mapbox/streets-v11',
        zoom: 2
      });

      map.on('load', function () {
        map.loadImage(
          './pin.png',
          function (error, image) {
            if (error) throw error;
            map.addImage('pin', image);
            // Add a GeoJSON source with 2 points
            map.addSource('localgroups', {
              type: 'geojson',
              cluster: false,
              data: './geo.json'
            });
            map.addLayer({
              id: 'allpoints',
              type: 'symbol',
              source: 'localgroups',
              layout: {
                'icon-image': 'pin',
                'icon-size': 0.3
              }
            });
          }
        );
        map.on('click', 'allpoints', function (e) {
          var coordinates = e.features[0].geometry.coordinates.slice();
          var description = e.features[0].properties.description;
          // Ensure that if the map is zoomed out such that multiple
          // copies of the feature are visible, the popup appears
          // over the copy being pointed to.
          while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
            coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
          }
          new mapboxgl.Popup()
            .setLngLat(coordinates)
            .setHTML(description)
            .addTo(map);
        });
      });
      // Change the cursor to a pointer when the mouse is over the places layer.
      map.on('mouseenter', 'places', function () {
        map.getCanvas().style.cursor = 'pointer';
      });

      // Change it back to a pointer when it leaves.
      map.on('mouseleave', 'places', function () {
        map.getCanvas().style.cursor = '';
      });
    </script>

    N.B. Map shows only communities with more than 10 members and more than 5 transactions in the last 3 months.
    <br /><br />
    <table>
      <thead>
        <tr>
          <th></th>
          <th>Platform</th>
          <th>Description</th>
          <th>Software</th>
          <th>Members</th>
          <th>Active sites</th>
          <th>12 months transactions</th>
        </tr>
      </thead>
      <tbody>
    <?php
      foreach (array_reverse(unserialize(file_get_contents('table.txt'))) as $type => &$sites) :
        $members = $groups = $transactions = $notfirst = 0;
        ?><?php foreach ($sites as $url => &$info) : ?>
          <tr>
            <td>
              <?php if (!$notfirst)print $type == 'member' ? '<font color=red>Credit commons collective</font>' : '<font color=gray>Other</font>'; $notfirst=1; ?>
            </td>
            <td>
                <a href="http://<?php print $info['url']; ?>"><?php print $info['name']; ?>
            </td>
            <td>
              <?php print $info['comment']; ?>
            </td>
            <td>
              <?php print $info['software']; ?>
            </td>
            <td>
              <?php $members += $info['members'];print $info['members']; ?>
            </td>
            <td>
              <?php $groups += $info['groups']; print $info['groups']; ?>
            </td>
            <td>
              <?php $transactions += $info['transactions']; print $info['transactions']; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <tr>
          <th></th>
          <th>Total</th>
          <th></th>
          <th></th>
          <th><?php print $members; ?></th>
          <th><?php print $groups; ?></th>
          <th><?php print $transactions; ?></th>
        </tr>
        <tr><td><br /></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </body>
</html>
