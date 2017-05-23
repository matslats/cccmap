# cccmap
A mini-project to make maps work better for complementary currency networks, first by showing their own, and secondly by publishing a csv file for the public, suitable for converting into a map.
This should help efforts to collate data from all platforms for study and to testify to the growth of complementary currencies.

**1. publish your own geojson file.
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
      "description" => "blah blah" //html with more details.
      "icon" => [
        "iconUrl" => "http://mysite/map-pin.png",
        "iconSize" => [25, 25], // size of the icon
        "iconAnchor" => [12, 12], // point of the icon which will correspond to marker's location
        "popupAnchor" => [0, 13], // point from which the popup should open relative to the iconAnchor
      ]
    ]
  ];
}
$geoJson = [//this is in the wrong place
  'type' => 'FeatureCollection',
  'features' => $allPoints
];
file_put_contents('all-exchanges-geo.json', json_encode($geoJson));//maybe best at the web root?
```
Ensure the list is kept up to date either with cron or with caching strategy.
Now your sites exist in a standard format, you can publish the geojson. Next you probably want to render these point on your own site. This can be done again with the help of mapbox.com and the api key you created above.

** 2 Render the geojson on your site.
Add these to your <header>:
<script src="https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.js"></script>
<link href="https://api.mapbox.com/mapbox.js/v3.1.0/mapbox.css" rel="stylesheet" />
<meta name='viewport' content='initial-scale=1,maximum-scale=1,user-scalable=no' />
Then this to your body:
<div id="map" style="width: 90%; height: 600px;"></div>
And AFTER that, in the body, this:
```javascript
<script>
  L.mapbox.accessToken = 'pk.eyJ1IjoibWF0c2xhdHMiLCJhIjoiY2oyeXcxdzdmMDBhNTMyanNmbzN1dGt2cSJ9.XUw8z45hXx8MoXgQ-G5QUw';
  var map = L.mapbox.map('map', 'mapbox.streets').setView([40, 0], 4);
  var myLayer = L.mapbox.featureLayer().addTo(map);
  var geoJson = <?php include './all-exchanges-geo.json'; ?>
  myLayer.on('layeradd', function(e) {
    var marker = e.layer,
      feature = marker.feature;
    marker.setIcon(L.icon(feature.properties.icon));
  });
  myLayer.setGeoJSON(geoJson);
</script>
```
and the points should show on the page. note that the map is centred on lat +40 and lon 0, and zoomed to 4. You might have to add an apache directive (in .htaccess perhaps) to execute php on a page with an html extension
`AddType application/x-httpd-php .html`

**3. Share your site data for others (Such as the Credit Commons Collective) to aggregate and process.
Construct and (ideally) 'file_put_contents' a csv file with the following columns:
"url" //the clean address of the site ready to use in a link. the http is not needed, and usually not the www e.g. mysite.com
"latitude" //a floating point number between -90 and +90
"longitude" //a floating point number between -180 and +180
"WKT" //If lat & lon not supplied, an address string we can attempt to geocode
"title" //the name of the exchange
"description" //any other brief text about the exchange, suitable for a map bubble!
"logo" //absolute url of the exchange's logo, smaller is better.
"active_members" //number of members in the exchange you deem to be 'active' i.e. who might have visited or traded recently.
"year_transactions" //number of transactions in the last year.
Remember there are php functions to help you generate and write csv files. It should be accessible from your organisation's web root directory as geo.csv
There are several examples of all these files in this repository.

This format is subject to change and all maps being aggregated will be notified.

Please look on http://complementarycurrency.org/geo
Real world changes won't appear on maps immediately because both the sites, and the aggregator are likely to be keeping caches.

To see the code we used to aggregate all the csv files and present a consistent look and feel, contact us.