<!DOCTYPE html>
<html lang="en-US">
  <head>
    <title>Major community currency networks</title>
    <script src='https://api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.js'></script>
    <link href='https://api.mapbox.com/mapbox-gl-js/v1.12.0/mapbox-gl.css' rel='stylesheet' />
    <meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
    <meta charset="UTF-8">

    <style>
        th {background-color: #dedede;}
    </style>
    <?php include './mapbox.conf.php';?>
  </head>
  <body>
    Zoom in with your mouse to see more sites.
    <div id='map' style='width: 90%; height: 800px;'></div>
    N.B. Map shows only communities with more than 10 members and more than 5 transactions in the last 3 months.
    <script>// Create the map using tiles
      mapboxgl.accessToken = '<?php print MAPBOX_ACCESS_TOKEN; ?>';
      var map = new mapboxgl.Map({
        container: 'map',
        style: '<?php print MAPBOX_STYLE; ?>',
        center: [0, 51],
        zoom: 2.5
      });
      map.setRenderWorldCopies(status === 'false');
      map.loadImage(
        './pin.png',
        function(error, image) {
          map.addImage('pin', image);
        }
      );
      map.loadImage(
        './pin-nonmem.png',
        function(error, image) {
          map.addImage('pinnon', image);
        }
      );
      map.on('load', function() {
        map.addSource('live', {
          'type': 'geojson',
          'data': './live.geo.json'
        });
        map.addSource('scraped', {
          'type': 'geojson',
          'data': './scraped.geo.json'
        });

        map.addLayer({
          'id': 'live',
          'type': 'symbol',
          'source': 'live',
          'layout': {
            'icon-image': 'pin',
            'icon-allow-overlap': true
          }
        })
        map.addLayer({
          'id': 'scraped',
          'type': 'symbol',
          'source': 'scraped',
          'layout': {
            'icon-image': 'pinnon',
            'icon-allow-overlap': true
          }
        })

        var popup = new mapboxgl.Popup({
          closeButton: false,
          closeOnClick: false
        });
        map.on('click', 'live', dopopup);
        map.on('click', 'scraped', dopopup);
        map.on('mouseenter', 'points', function(e) {
          map.getCanvas().style.cursor = 'pointer';
        });
        map.on('mouseleave', 'points', function(e) {
          map.getCanvas().style.cursor = '';
        });

        function dopopup(e) {
          var coordinates = e.features[0].geometry.coordinates.slice();
          var description = e.features[0].properties.network +'<br />'+ e.features[0].properties.description;
          // Ensure that if the map is zoomed out such that multiple copies of the feature are visible, the popup appears over the copy being pointed to.
          while (Math.abs(e.lngLat.lng - coordinates[0]) > 180) {
            coordinates[0] += e.lngLat.lng > coordinates[0] ? 360 : -360;
          }
          popup
          .setLngLat(coordinates)
          .setHTML(description)
          .addTo(map);
        };
      });

    </script>
      <?php
      const LAYER_LIVE = 'live';
      const LAYER_SCRAPED = 'scraped';
      $sources = unserialize(file_get_contents('table.txt'));
      usort($sources, 'sourcesort');
      $groups = 0;
      foreach ($sources as $info)$groups += $info['groups'];
      ?>
    N.B. Map shows only communities with more than 10 members.
    <br /><br />
    <table>
      <thead>
        <tr>
          <th>Platform</th>
          <th>Description</th>
          <th>Software</th>
          <th>Members</th>
          <th>Active sites (<?php print $groups ?>)</th>
          <th>12 months transactions</th>
        </tr>
      </thead>
      <tbody>
    <?php foreach ($sources as $info) :
        if (!$info['groups']) {
          continue;
        }
        $groups += $info['groups'];
        if (is_numeric($info['members'])) {
          $members += $info['members'];
        }
        if (is_numeric($info['transactions'])) {
          $transactions += $info['transactions'];
        }
        ?>

          <tr>
            <td>
              <a href="http://<?php print $info['url']; ?>"><?php print $info['name']; ?></a>
                <?php print $type != LAYER_LIVE ? '': ' (scraped)'; ?>
            </td>
            <td>
              <?php print $info['comment']; ?>
            </td>
            <td>
              <?php print $info['software']; ?>
            </td>
            <td>
              <?php print $info['members']; ?>
            </td>
            <td>
              <?php print $info['groups']; ?>
            </td>
            <td>
              <?php  print $info['transactions']; ?>
            </td>
          </tr>
        <tr><td><br /></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </body>
</html>

<?php
function sourcesort(array $a, array $b): bool {
  if ($a[LAYER_LIVE] == $b[LAYER_LIVE]) {
    return $a['groups'] < $b['groups'];
  }
  return $a[LAYER_LIVE] > $b[LAYER_LIVE];
}
