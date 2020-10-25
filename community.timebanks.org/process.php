<?php

$source_html = 'https://community.timebanks.org/';
$target_csv = __DIR__."/geo.csv";

preg_match(
  '/jQuery.extend\(Drupal.settings, ([^;]+)\)/',
  file_get_contents($source_html),
  $matches
);
$source_geo_json = str_replace('\\', '', $matches[1]);
$json = json_decode($source_geo_json);

foreach ($json->gmap->auto1map->markers as $tb) {
  $points[] = [
    'latitude' => $tb->latitude,
    'longitude' => $tb->longitude,
    'title' => $tb->opts->title,
    'url' => 'https://community.timebanks.org'. $tb->link
  ];
}
$fp = fopen($target_csv, 'w+');
fputcsv($fp, array_keys(reset($points)));
foreach ($points as $point) {
  fputcsv($fp, $point);
}
fclose($fp);