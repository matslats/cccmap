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
ini_set('display_errors', TRUE);

const LAYER_LIVE = 'live';
const LAYER_SCRAPED = 'scraped';
const MIN_TRANSACTIONS_YEAR = 10;
include './mapbox.conf.php';

$errors = "";
// Pull in the feeds and build a single geojson file.

$allPoints = [];

//rebuild the local cache if it is more than a day old
$sources = [
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
    'handmade PHP',
    ''
  ],
  'integralces.net' => [
    'Integral CES',
    'Drupal (custom)',
    'rebuild of CES platform of Spanish cooperatives.'
  ],
  // put the member networks last so our pins appear on top
  'communityforge.net' => [
    'Community Forge',
    'Drupal (<a href="http://drupal.org/project/cforge">Hamlets</a>)',
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
    'Free SAAS for community exchanges',
  ],
  'timeoverflow.org' => [
    'CoopDevs',
    '<a href="https://github.com/coopdevs/timeoverflow">Time Overflow</a>',
    'Free hosted and supported sites',
  ]
];

$all_points = $all_points = [];
foreach ($sources as $url => &$info) {
  $points = [];
  $keys = ['name', 'software', 'comment', 'live'];
  $info = array_combine($keys, array_pad($info, count($keys), NULL));
  //In testing mode don't retrive from live sites
  $live_url = 'https://'.$url.'/geo.csv';
  if ($csvHandle = @fopen($live_url, 'r')) {
    if ($result = geo_csv_points($csvHandle, $info['name'])) {
      list($info['groups'], $info['members'], $info['transactions'], $points) = $result;
      $info['live'] = TRUE;
      $live[] = $info['name'];
    }
  }
  if (!$points and $csvHandle = @fopen($url.'/geo.csv', 'r')) {//look locally
    list($info['groups'], $info['members'], $info['transactions'], $points) = geo_csv_points($csvHandle, $info['name']);
  }
  if (!$points and $csvHandle = fopen('https://raw.githubusercontent.com/matslats/cccmap/master/'.$url.'/geo.csv?', 'r')) {
    list($info['groups'], $info['members'], $info['transactions'], $points) = geo_csv_points($csvHandle, $info['name']);
    $info['comment'] .= ' (unfiltered data)';
  }
  $messages[] = '<font color="'.(in_array($info['name'], $live) ? 'green':'orange').'">Taken '.count($points) .' points from '.$live_url.'</font>';

  $all_points = array_replace_recursive($all_points, $points);
}
$messages[] = "Total ".count($all_points).' map points.';

//sort out the points and put them in layers
foreach ($all_points as $unique => $point) {
  $layer = in_array($point['properties']['network'], $live) ? LAYER_LIVE : LAYER_SCRAPED;
  $layers[$layer][] = $point;
}

file_put_contents('table.txt', serialize($sources));

writeGeoJson(LAYER_LIVE, (array)$layers[LAYER_LIVE]);
writeGeoJson(LAYER_SCRAPED, (array)$layers[LAYER_SCRAPED]);

print '<div class="mapgregator">'.implode("<br />\n", $messages).'</div>';
exit;

function writeGeoJson($filename, array $points) {
  global $messages;
  $filename = $filename.'.geo.json';
  if (empty($points)) {
    $messages[] = "<font color=orange>No points to write to $filename</font>";
    return;
  }
  global $messages;
  $geoJson = [
    'type' => 'FeatureCollection',
    'features' => $points
  ];
  $string = json_encode($geoJson);
  if (empty($string)) {
    $messages[] = "<font color=red>Failed to json_encode points for $filename. Was it UTF8 Encoded?</font>";
  }
  $messages[] =  file_put_contents($filename, $string) ?
    'Wrote '. count($points) .' nodes to '.$filename :
    "<font color=red>Failed to write ". count($points) ." to $filename</font>";
}

/**
 *
 * @param resource $csvHandle
 * @param string $networkName
 *
 * @return array
 *   The geojson structure.
 */
function geo_csv_points($csvHandle, $networkName) {
  global $geocoded, $messages;
  $firstrow = 1;
  $points = [];
  $skipped = ['inactive' => 0, 'baddata' => 0];
  $members = $transactions = $rows = 0;
  $headings = fgetcsv($csvHandle);
  if (count($headings) < 3) {
    return array();
  }
  while($data = fgetcsv($csvHandle)) {
    $rows++;
    if (count($headings) <> count($data)) {
      if ($firstrow){
        $messages[] = "<font color=\"red\">Wrong number of columns in $networkName: ".implode(', ' , $data).'| should be:'.implode(', ' , $headings) .'</font>';
      }
      $data = array_pad($data, count($headings), NULL);
      $firstrow = 0;
    }
    $row = array_combine($headings, $data);
    //skip low volume sites.
    if (isset($row['active_members']) and $row['active_members'] < 10) {
      $skipped['inactive']++;
      continue;
    }
    elseif (isset($row['year_transactions']) and $row['year_transactions'] < 10) {
      $skipped['inactive']++;
      continue;
    }
    // Legacy column name still published by hourworld.
    elseif (isset($row['3month_transactions']) and $row['3month_transactions'] < 3) {
      $skipped['inactive']++;
      continue;
    }
    elseif (empty($row['title'])){
      $skipped['baddata']++;
      $messages[] = "no title ".print_r($row, 1);
      continue;
    }
    $row['title'] = utf8_encode($row['title']);
    //"url", "latitude", "longitude", "WKT", "title", "description", "logo", "active_members", "3month_transactions"
    if (empty($row['latitude']) || empty($row['longitude'])) {
      if (!empty($row['WKT']) and defined('MAPBOX_ACCESS_TOKEN')) {
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
        "name" => trim($row['title']),
        "network" => $networkName,
        'description' => '',
      ]
    ];
    $bubble = &$point['properties']['description'];

    if (!empty($row['logo']) and substr($row['logo'], 0, 4) != 'http') {
      $row['logo'] = 'http://'.$row['logo'];
    }
    if (!empty($row['url'])) {
      if (substr($row['url'], 0, 7) != 'http://') {
        $row['url']= 'http://'.strtolower($row['url']);
      }
      $bubble = '<a href="'.$row['url'].'">'.$row['title'].'</a><br />';
      if (isset($row['logo'])) {
        $bubble .= '<a href="'.$row['url'].'"><img src="'.$row['logo'].'" width = "55" align = "left" /></a>';
      }
    }
    else {
      $bubble = $row['title'].'<br />';
      if (!empty($row['logo'])) {
        $bubble .= '<img src="'.$row['logo'].'" width=55 align=left/>';
      }
    }
    if (!empty($row['description'])) {
      $bubble .= $row['description'].'<br />';
    }
    
    if (!json_encode($bubble)) {
       $messages[] = '<font color=red> non-UTF8 string for '.$row['title'].': '.$bubble .'</font>';
       $bubble = utf8_encode($bubble);
       $messages[] = '<font color=green> corrected to '.$row['description'] .'</font>';
     }

    $points[] = $point;
    if (isset($row['active_members']) and is_numeric($row['active_members'])) {
      $members += $row['active_members'];
    }
    $name = strtolower(str_replace('SEL', '', $point['properties']['name']));
    $unique_name = preg_replace('/\s+/', '', $name);
    $loc = substr($point['geometry']['coordinates'][0], 0, 3).substr($point['geometry']['coordinates'][1], 0, 3);

    $points[$unique_name.$loc] = $point;
    $members += isset($row['active_members']) ? $row['active_members'] : 0;
    $transactions += isset($row['year_transactions']) ? $row['year_transactions'] : 0;
  }
  if ($skipped['inactive']) {
    $messages[] = '<font color=green>'.$networkName.' skipped '.$skipped['inactive'] .' inactive groups out of '.$rows.'</font>';
  }
  if ($skipped['baddata']) {
    $messages[] = '<font color=red>'.$networkName.' skipped '.$skipped['baddata'] .' groups for bad data out of '.$rows.'</font>';
  }
  return [count($points), $members ? : 'unknown', $transactions ? : 'unknown', $points];
}

function geocode($string) {
  global $messages;
  $wkt = urlencode($string);
  $url = implode('/', [
    'https://api.mapbox.com',
    'geocoding',
    'v5',
    'mapbox.places',
    urlencode($string) .'.json'
  ]);
  $result = file_get_contents($url."?access_token=".MAPBOX_ACCESS_TOKEN);
  $obj = json_decode($result);
  if (is_object($obj)) {
    if (count($obj->features)) {
      return $obj->features[0]->geometry->coordinates;
    }
    else {
      $messages[] =  "Geocoding '$string' failed: ". print_r($obj);
    }
  }
  else {
    echo "<br />Geocoding exceeded limit";
    exit;
  }
}
