<html>
  <head>
    <title>Major community currency networks</title>
    <script src='https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.js'></script>
    <link href='https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.css' rel='stylesheet' />
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <meta charset="UTF-8">
    <style>
        .member{}
        .nonmem{filter: saturate(40%) opacity(50%);}
        th {background-color: #dedede;}
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
