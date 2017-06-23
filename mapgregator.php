<?php
/**
 * @file
 * Retrieves exchange data from all the networks we know about it, and builds a geojson file
 * Should be called by cron daily or weekly
 *
 * Objectives of this map:
 * - help people find the nearest system
 * - show how big we are
 * - provide data to academics.
 */
ini_set('max_execution_time', 200);

$errors = "";
// Pull in the feeds and build a single geojson file.

$allPoints = [];

//rebuild the local cache if it is more than a day old
$urls = [
  //'route-des-sel.org' => ['(Route des SEL)', 'rds']
  'timebanking.com.au' => ['Timebanking NSW',  'tbnsw'], //#00AAF4
  'hourworld.org' => ['hOurworld', 'hourworld'], //#255625
  'community.timebanks.org' => ['TB USA', 'tbusa'], //#104A91
  'integralces.net' => ['Integral CES', 'ices'],//#9c3;
  // put the member networks last so our pins appear on top
  'communityforge.net' => ['Cforge', 'cforge ccc'], //'rgb(0, 113, 188)
  'communityexchange.net.au' => ['CES (Oz)', 'cesoz ccc'], //rgb(84, 197, 208)
  'community-exchange.org' => ['CES (Main)', 'cesmain ccc'], //#6fbd44
  'letslinkuk.net' => ['LETSlink UK', 'letslinkuk'], //#255625
  'static' => ['', 'other'], //#255625
];

foreach ($urls as $url => $info) {
  //In testing mode don't retrive from live sites
  if (FALSE && $csvHandle = fopen('http://'.$url.'/geo.csv', 'r')) {
    $color = "green";
  }
  elseif ($csvHandle = fopen('file:///home/matslats/cccmap/'.$url.'/geo.csv', 'r')) {
    $color = "red";
  }
  elseif ($csvHandle = fopen('https://raw.githubusercontent.com/matslats/cccmap/master/'.$url.'/geo.csv?', 'r')) {
    $color = "orange";
  }
  $retrieved = geo_csv_points($csvHandle, $info[0], $info[1]);
  $messages[] = "\n<font color=\"$color\">Taken ".count($retrieved) ." points from  $url</font>";
  $allPoints = array_merge($allPoints, $retrieved);
  if ($geocoded) {
    //file_put_contents($url.'.csv', implode("\n", $geocoded));
    $geocoded = "";
  }
}

$messages[] = "Total ".count($allPoints).' map points.';
$geoJson = [//this is in the wrong place
  'type' => 'FeatureCollection',
  'features' => reduce_duplicates($allPoints)
];
//now is the time to check for duplicates
$messages[] = "Saved ".count($geoJson['features']).' map points.';

file_put_contents('geo.json', json_encode($geoJson));

print '<div class="mapgregator">'.implode("<br />\n", $messages).'</div>';

/**
 *
 * @param type $csvHandle
 * @param type $color
 *
 * @return array
 */
function geo_csv_points($csvHandle, $networkName, $class) {
  global $geocoded, $messages;
  $points = [];
  $headings = fgetcsv($csvHandle);
  while($data = fgetcsv($csvHandle)) {
    if (count($headings) <> count($data)) {
      die('wrong num of columns '.$networkName);
    }
    $row = array_combine($headings, $data);

    if (isset($row['active_members']) and $row['active_members'] < 10) continue;
    //"url", "latitude", "longitude", "WKT", "title", "description", "logo", "active_members", "3month_transactions"
    if (empty($row['latitude']) || empty($row['longitude'])) {
      if (!empty($row['WKT'])) {
        //not sure if these are the right way around
        list($row['longitude'], $row['latitude'], ) = geocode($row['WKT']);
        $geocoded[] = '"'.$row['WKT'] .'",'. $row['latitude'] .','. $row['longitude'];
      }
      else {
        print_r($row);
        die('no coords or WTK '.$networkName);
      }
    }
    $point = [
      'type' => 'Feature',
      'geometry' => [
        'type' => 'Point',
        'coordinates' => [$row['longitude'],  $row['latitude']]
      ],
      'properties' => [
        "name" => $row['title'],
        "icon" => [
          "iconUrl" => "/redpin.png",
          "iconSize" => [32, 32], // size of the icon
          "iconAnchor" => [25, 31], // point of the icon which will correspond to marker's location
          "popupAnchor" => [0, -35], // point from which the popup should open relative to the iconAnchor
          "className" => "$class"
        ]
      ]
    ];
    $bubble = &$point['properties']['description'];
    if (isset($row['url'])) {
      if (substr($row['url'], 0, 7) != 'http://') {
        $row['url']= 'http://'.$row['url'];
      }
      $bubble = '<a href="'.$row['url'].'">'.$row['title'].'</a> - '.$networkName;
      if (isset($row['logo'])) {
        $bubble .= '<br /><a href="'.$row['url'].'"><img src="'.$row['logo'].'" width = "55" align = "left"/></a>';
      }
    }
    else {
      $bubble = $row['title'] .' - '.$networkName;
      if (isset($row['logo'])) {
        $bubble .= '<br /><img src="'.$row['logo'].'" width=55 align=left/>';
      }
    }

    if (isset($row['description'])) {
      $bubble .= '<br />'.$row['description'];
    }
    if (isset($row['active_members'])) {
      $bubble .= '<br /><strong>'.$row['active_members']. '</strong> Active members';
    }
    if (isset($row['3month_transactions'])) {
      $bubble .= '<br /><strong>'.$row['3month_transactions']. '</strong> transactions last year';
    }
    $points[] = $point;
  }
  if (empty($points)) {
    print_r($headings);
    die('No results');
  }
  return $points;
}

function geocode($string) {
  $wkt = urlencode($string);
  $url = implode('/', [
    'https://api.mapbox.com',
    'geocoding',
    'v5',
    'mapbox.places',
    urlencode($string) .'.json'
  ]);
  $result = file_get_contents($url."?access_token=pk.eyJ1IjoibWF0c2xhdHMiLCJhIjoiY2oyeXcxdzdmMDBhNTMyanNmbzN1dGt2cSJ9.XUw8z45hXx8MoXgQ-G5QUw");
  $obj = json_decode($result);
  if (is_object($obj)) {
    if (count($obj->features)) {
      return $obj->features[0]->geometry->coordinates;
    }
    else {
      echo "<br />Geocoding '$string' failed: ". print_r($obj);
    }
  }
  else {
    echo "<br />Geocoding exceeded limit"; die();
  }
}

function reduce_duplicates($allPoints) {
  global $messages;
  //build an index
  foreach ($allPoints as $id => $point) {
    $title = trim(strtolower(str_replace('SEL', '', $point['properties']['name'])));
    $titles[$title][] = $id;
    $names[] = $title;
  }
  $dupenames = [];
  foreach (array_count_values($names) as $title => $count) {
    if ($count == 1) {
      continue;
    }
    $compare = [];
    foreach ($titles[$title] as $id) {
      $compare[$id]['loc'] = intval($allPoints[$id]['geometry']['coordinates'][0]) .  intval($allPoints[$id]['geometry']['coordinates'][1]);
      $compare[$id]['len'] = strlen($allPoints[$id]['properties']['description']);
    }
    //assume there is no more than two duplicates
    list($id1, $id2) = array_keys($compare);
    if ($compare[$id1]['loc'] == $compare[$id2]['loc']) {
      $remove = ($compare[$id1]['len'] > $compare[$id2]['len']) ? $id2 : $id1;
      unset($allPoints[$remove]);
      $dupeNames[] = $title;
    }
    else {
      $messages[] = "Retained non-duplicate exchanges: ".$title;
    }
  }
  $messages[] = "removed ".count($dupeNames) ." duplicates: ".implode(', ', $dupeNames);
  return array_values($allPoints);
}
