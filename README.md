# cccmap
Code to take data from some complementary currency platforms, combine them into one geojson.
This is just the beginning of an effort to collate data from all platforms to be accessible to study and to testify to the growth of complementary currencies.
We support platforms publishing with either of two formats.

Complementary currency platforms even those who don't have the latitude and longitude can publish a domain.com/geo.csv, with the following columns:
"url" //the clean address of the site ready to use in a link, e.g. http://mysite.com
"latitude" //a floating point number between -90 and +90
"longitude" //a floating point number between -180 and +180
"WKT" //If lat & lon not supplied, an address string we can attempt to geocode
"title" //the name of the exchange
"description" //any other brief text about the exchange, suitable for a map bubble!
"logo" //the smallest version you have of the exchange's logo
"active_members" //number of members you deem to be 'active'
"3month_transactions" //number of transactions in the last 3 months.

Platforms who do have geocoordinates can publish in the geojson format. Note that one feature collection contains many features, each of which is a community. Note that the title and description are urlencoded using a function such as urlencode() in php or equivalent. Also note that the href and logo are escaped using a function such htmlentities() in php, or equivalent.
{
  "type":"FeatureCollection",
  "features":[
    {
      "type":"Feature",
      "geometry":{
        "type":"Point",
        "coordinates":[
          146.921099,
          -31.2532183
        ]
      },
      "properties":{
        "title":"My Timebank",
        "description":"we are a really cool timebank",
        "href":"https:\/\/mydomain.com",
        "logo":"https:\/\/mydomain.com.au\/files\/pictures\/picture-6-1396253258.jpg",
        "active_members":"100"
        "3month_transactions":"1000"
      }
    }
  ]
}
This json file can be embedded directly on the platform's own site using some javascript, which we will share later.

Please notify us when your files are ready; we are currently pulling files from:
http://communityforge.net/geo.json
http://timebanking.com.au/geo.csv
http://route-des-sel.org/geo.csv
http://community-exchange.org/geo.csv (mocked)
http://timebanks.community.timebanks.org/geo.csv (mocked)
http://communityexchange.net.au/geo.csv (mocked)
http://hourworld.org/geo.csv (mocked)
http://integralcecs.net/geo.csv (mocked)

The aggregated map is currently being served from github, and it can be viewed directly https://github.com/matslats/cccmap/blob/master/all.json
It can also be embedded into any web page with the following snippet
<script src="https://embed.github.com/view/geojson/matslats/cccmap/master/cforge.json?height=768&width=1024"></script>

This format is subject to change and you will be notified.
Please look on http://complementarycurrency.org/geo
Changes to the csv file will take up to a day to appear on the map.
