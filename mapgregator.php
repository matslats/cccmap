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
$urls['nonmem'] = [
  'route-des-sel.org' => [
    'Route des SEL',
    'Drupal (custom)',
    'A French platform for house sharing between LETS members'
  ],
  'associazionenazionalebdt.it' => [
    'Associazione Nazionale Banche del Tempo',
    'None?',
    'Italian umbrella org'
  ],
  'letslinkuk.net' => [
    'LETSlink UK',
    '<a href="https://github.com/cdmweb/Local-Exchange-UK">Local Exchange</a>',
    'UK LETS umbrella org'
  ],
  'static' => [
    'Other',
    '<a href="https://github.com/cdmweb/Local-Exchange-UK">Local Exchange</a>',
    'Some other sites we know about running Local Exchange'
  ],
  'timebanking.com.au' => [
    'Timebanking NSW',
    'Drupal (Custom)',
    'Commissioned by the state government to run its timebanking programme'
  ],
  'hourworld.org' => [
    'hOurworld',
    'handmade PHP',
    'Free timebanking SAAS'
  ],
  'community.timebanks.org' => [
    'Timebanks USA',
    '',
    ''
  ],
  'integralces.net' => [
    'Integral CES',
    'Drupal (custom)',
    'rebuild of CES platform of Spanish cooperatives.'
  ]
];
$urls['member'] = [
  // put the member networks last so our pins appear on top
  'communityforge.net' => [
    'Community Forge',
    'Drupal (<a href="http://drupal.org/project/cforge">Hamlets)',
    'Free hosting and support for Hamlets sites'
  ],
  'communityexchange.net.au' => [
    'CES (Oz)',
    'handmade ASP',
    'Clone of main CES for Australian network'
  ],
  'community-exchange.org' => [
    'CES (Main)',
    'handmade ASP',
    'Free SAAS for community exchanges'
  ]
];

foreach ($urls as $type => &$sites) {
  foreach ($sites as $url => &$info) {
    $points = [];
    $info = array_combine(['name', 'software', 'comment'], $info);
    //In testing mode don't retrive from live sites
    if ($csvHandle = @fopen('http://'.$url.'/geo.csv', 'r')) {
      if ($result = geo_csv_points($csvHandle, $info['name'], $type)) {
        list($info['groups'], $info['members'], $info['transactions'], $points) = $result;
      }
    }
    else {
      $messages[] = '<font color=orange>No file at '.'http://'.$url.'/geo.csv'.'</font>';
    }
    if (!$points and $csvHandle = fopen('https://raw.githubusercontent.com/matslats/cccmap/master/'.$url.'/geo.csv?', 'r')) {
      list($info['groups'], $info['members'], $info['transactions'], $points) = geo_csv_points($csvHandle, $info['name'], $type);
      $info['comment'] .= ' (unfiltered data)';
    }
    //if (!$points and $csvHandle = @fopen($url.'/geo.csv', 'r')) {//look locally
    //  list($info['groups'], $info['members'], $info['transactions'], $points) = geo_csv_points($csvHandle, $info['name'], $type);
    //}
    $messages[] = "<font color=green>Taken ".count($points) ." points from ".$info['name']."</font>";

    $allPoints = array_merge($allPoints, $points);
    if ($geocoded) {
      //file_put_contents($url.'.csv', implode("\n", $geocoded));
      $geocoded = "";
    }
  }
}
file_put_contents('table.txt', serialize($urls));

$messages[] = "Total ".count($allPoints).' map points.';
$deduped = reduce_duplicates($allPoints);
$geoJson = [
  'type' => 'FeatureCollection',
  'features' => $deduped
];
//now is the time to check for duplicates
$string = json_encode($geoJson);
if ($string == FALSE) {
  $messages[] = '<font color=red>'.json_last_error_msg().'</font>';
}
$messages[] = "Saving ".count($geoJson['features']).' map points';

if ($string) {
  $result = file_put_contents('geo.json', $string);
  if ($result === FALSE) $messages[] = 'Failed to write';
  else $messages[] = 'Wrote '. $result .' bytes to geo.json';
}
print '<div class="mapgregator">'.implode("<br />\n", $messages).'</div>';

/**
 *
 * @param resource $csvHandle
 * @param string $networkName
 * @param string $class_name
 *
 * @return array
 *   The geojson structure.
 */
function geo_csv_points($csvHandle, $networkName, $class_name) {
  global $geocoded, $messages;
  $points = [];
  $members = $transactions = 0;
  $headings = fgetcsv($csvHandle);
  if (count($headings) < 3) {
    return array();
  }
  while($data = fgetcsv($csvHandle)) {
    if (count($headings) <> count($data)) {
      print_r($headings);print_r($data);
      die('wrong num of columns '.$networkName);
    }
    $data = array_pad($data, count($headings), array());
    $row = array_combine($headings, $data);
    //skip low volume sites.
    if (isset($row['active_members']) and $row['active_members'] < 10) {
      continue;
    }
    //"url", "latitude", "longitude", "WKT", "title", "description", "logo", "active_members", "3month_transactions"
    if (empty($row['latitude']) || empty($row['longitude'])) {
      if (!empty($row['WKT'])) {
        //not sure if these are the right way around
        list($row['longitude'], $row['latitude'], ) = geocode($row['WKT']);
        $geocoded[] = '"'.$row['WKT'] .'",'. $row['latitude'] .','. $row['longitude'];
      }
      else {
        continue;
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
          "iconUrl" => "pin.png",
          "iconSize" => [32, 32], // size of the icon
          "iconAnchor" => [25, 31], // point of the icon which will correspond to marker's location
          "popupAnchor" => [0, -35], // point from which the popup should open relative to the iconAnchor
          "className" => "$class_name"
        ]
      ],
      'description' => ''
    ];
    $bubble = &$point['properties']['description'];

    if (!empty($row['logo']) and substr($row['logo'], 0, 4) != 'http') {
      $row['logo'] = 'http://'.$row['logo'];
    }
    if (isset($row['url'])) {
      if (substr($row['url'], 0, 7) != 'http://') {
        $row['url']= 'http://'.$row['url'];
      }
      $bubble = '<a href="'.$row['url'].'">'.$row['title'].'</a> - '.$networkName .'<br />';
      if (isset($row['logo'])) {
        $bubble .= '<a href="'.$row['url'].'"><img src="'.$row['logo'].'" width = "55" align = "left" /></a>';
      }
    }
    else {
      $bubble = $row['title'] .' - '.$networkName .'<br />';
      if (!empty($row['logo'])) {
        $bubble .= '<img src="'.$row['logo'].'" width=55 align=left/>';
      }
    }

    if (!empty($row['description'])) {
      if (!json_encode($row['description'])) {
        $messages[] = '<font color=red> non-UTF8 string for '.$row['title'].': '.$row['description'] .'</font>';
        $row['description'] = utf8_encode($row['description']);
        $messages[] = '<font color=green> corrected to '.$row['description'] .'</font>';
        continue;
      }
      $bubble .= $row['description'].'<br />';
    }

    if (isset($row['active_members'])) {
      $bubble .= '<strong>'.$row['active_members']. '</strong> Active members<br />';
    }
    if (isset($row['year_transactions'])) {
      $bubble .= '<strong>'.$row['year_transactions']. '</strong> transactions last year';
    }
    if (!json_encode($bubble)) {
      $messages[] = '<font color=red> non-UTF8 string for '.$bubble .'</font>';
      continue;
    }
    $points[] = $point;
    $members += isset($row['active_members']) ? $row['active_members'] : 0;
    $transactions += isset($row['year_transactions']) ? $row['year_transactions'] : 0;
  }
  return [count($points), $members ? : 'unknown', $transactions ? : 'unknown', $points];
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
