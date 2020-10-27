<?php
$base_url = 'https://www.i-share-economy.org/';
$map_path= '/kos/WNetz?art=CompanyCategory.show&id=29';
$target_file = __DIR__.'/geo.csv';

preg_match_all('/LatLng(.*),imageCat/', file_get_contents($base_url.$map_path), $groups);
foreach ($groups[1] as $string) {
  // 49.62872,8.371975),"Verschenk- und Tauschb&ouml;rse Worms",'/kos/WNetz?art=Company.show&id=182'
  preg_match('/^\(([0-9.]+),([0-9.]+)\),"([^"]+)".\'(.*)\'$/', $string, $matches);
  $points[] = [
    'latitude' => $matches[1],
    'longitude' => $matches[2],
    'title' => html_entity_decode($matches[3]),
    'url' => $base_url .$matches[4]
  ];
}

$fp = fopen($target_file, 'w+');
fputcsv($fp, array_keys(reset($points)));
foreach ($points as $point) {
  fputcsv($fp, $point);
}
fclose($fp);
