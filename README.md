# cccmap
Code to take data from some complementary currency platforms, combine them into one geojson.  
This is just the beginning of an effort to collate data from all platforms to be accessible to study and to testify to the growth of complementary currencies.  

Complementary currency platforms who want to participate are invited to produce a csv file (at their site root), and tell us the url.  
The CSV file should have the following columns:  
"url" //the clean address of the site ready to use in a link, e.g. http://mysite.com  
"latitude" //a floating point number between -90 and +90  
"longitude" //a floating point number between -180 and +180  
"WKT" //If lat & lon not supplied, an address string we can attempt to geocode   
"title" //the name of the exchange  
"description" //any other brief text about the exchange, suitable for a map bubble!   
"logo" //the smallest version you have of the exchange's logo  
"active_members" //number of members you deem to be 'active'  
"3month_transactions" //number of transactions in the last 3 months.  

This format is subject to change and you will be notified.  
Please look on http://complementarycurrency.org/geo  
Changes to the csv file will take up to a day to appear on the map.  
