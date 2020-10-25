<?php
$source_kml_file = __DIR__.'/points.kml';
$target_file = __DIR__.'/geo.csv';

$kml = simplexml_load_file($source_kml_file);
foreach ($kml->Document->children() as $child) {
  $point['title'] = (string)$child->name;
  //$point['title'] = utf8_encode($point['title']);
  preg_match('/http[^">]+/', (string)$child->description, $matches);
  $point['url'] = $matches[0];
  $point['description'] = trim(strip_tags((string)$child->description));
  //$point['description'] = utf8_encode($point['description']);
  @list($point['longitude'], $point['latitude']) = explode(',', (string)$child->Point->coordinates);
  if ($point['longitude'] and $point['latitude']){
    $points[] = $point;
  }
}
$fp = fopen($target_file, 'w+');
fputcsv($fp, array_keys(reset($points)));
foreach ($points as $point) {
  fputcsv($fp, $point);
}
fclose($fp);
