# cccmap
A mini-project to make maps work better for complementary currency networks, first by showing their own, and secondly by publishing a csv file for the public, suitable for converting into a map.
This should help efforts to collate data from all platforms for study and to testify to the growth of complementary currencies.

## 1. Share your site data for others (Such as the Credit Commons Collective) to aggregate and process.  
Construct and (ideally) `file_put_contents` a file called geo.csv in your web root with the following columns:  

|Fieldname|Description|Example|
|--- |---| ---|
|url|the address for use in a link. the 'http' is not needed, and usually not the www |mysite.com|
|latitude|a floating point number between -90 and +90| 45.93836|
|longitude|a floating point number between -180 and +180|6.827263|
|WKT|Well Known Text - (If lat & lon not supplied), an address string we can attempt to geocode| 
|title|the name of the exchange| Anytown Hour Bank |
|description|Or slogan. one sentence max| We do things for each other!|
|logo|absolute url of the exchange's logo, smaller is better|mysite.com/logo.png
|active_members|number of members in the exchange deemed 'active' e.g. have logged in or traded| 99|
|year_transactions|Transactions recorded in the last year. Try to exclude automated and mass transactions| 999|
|ads|Number of EXTANT offers + wants|999

There are several examples of all these files in this repository.  
Remember there are php functions to help you generate and write csv files.  
N.B. How do we ensure UTF8?  

This format is subject to change and all maps being aggregated will be notified.

Offical rendering is on http://creditcommons.net/map
N.B. Real world changes won't appear on maps immediately because both the sites, and the aggregator are likely to be keeping caches.

## 2. publish your own geojson file (optional)
Geojson is a standard format for publishing points on a map and info about them.
You can can click on a map to learn the coordinates here: http://latlong.net
or you can do it programmatically like this:
```php
// create a free account on mapbox.com to get the $access_token
function geocode($placename) {
  $wkt = urlencode($string);
  $url = implode('/', [
    'https://api.mapbox.com',
    'geocoding',
    'v5',
    'mapbox.places',
    urlencode($string) .'.json'
  ]);
  $access_token = 'xxxxxx';
  $result = file_get_contents($url."?access_token=$access_token");
  $obj = json_decode($result);
  if (is_object($obj)) {
    if (count($obj->features)) {
      return $obj->features[0]->geometry->coordinates;
    }
    else {
      echo "<br />Geocoding '$string' failed: ". print_r($obj);
    }
  }
```
Then build an array of your communities and iterate through, populating the points. Note that the longitude goes before latitude in this format.
```php
foreach ($myData as $site) {
  $allPoints[] = [
    'type' => 'Feature',
    'geometry' => [
      'type' => 'Point',
      'coordinates' => [$site['longitude'],  $site['latitude']]
    ],
    'properties' => [
      "title" => $site['name'], //plain text
      "description" => "blah blah" //html with more details. N.B. How do we ensure UTF8?
    ]
  ];
}
$geoJson = [//this is in the wrong place
  'type' => 'FeatureCollection',
  'features' => $allPoints
];
//This file can be viewed automatically in a github repository
file_put_contents('my_geo.json', json_encode($geoJson));//maybe best at the web root?
```
See the one at http://communityforge.net/geo.json, which is rebuilt regularly
Ensure the list is kept up to date either with cron or with caching strategy.
Next you probably want to render these point on your own site. This can be done again with the help of mapbox.com and the access token you created above.

## 3 Render the geojson on your site.
See index.php  for an example of how to do this. 
You may need a mapbox access token from http://mapbox.com

and the points should now render on the map. Note that this map is centred on lat +40 and lon 0, and zoomed to 4.  
You might have to add an apache directive (in .htaccess perhaps) to execute php on a page with an html extension.  
`AddType application/x-httpd-php .html`
Nginx is a bit more complicated.



To see the code we used to aggregate all the csv files and present a consistent look and feel, contact us.
