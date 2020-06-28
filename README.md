# UK-Coordinates
A proof of concept for geographical mapping of UK towns and postcodes, using CodePoint Open's downloadable dataset and Wikipedia's postcode out list of town names.
Automatic conversion from UTM formatted co-ordinates into latitude/longitude co-ordinates on database creation.
Allows looking up within a radius of any UK postcode, looking up the town name for a post code, or postcode lists within a town.

# Building your database to produce geographical for UK Postcodes and Towns
1) Install PHPCoord (https://github.com/dvdoug/PHPCoord)
2) Load OS-data csv's into /data (CodePoint Open) - (https://www.ordnancesurvey.co.uk/opendatadownload/products.html#CODEPO)
3) Set database configs in core/DBconfig
4) Run core/database.sql to create tables
5) Note: This may take a few minutes to run depending on your configuration

# Test Scripts
1) get_town_postcodes.php will return all postcodes attached to a town
2) find_postcode_out will return a town name for a postcode out (eg. BN1)
3) find_postcode_zone will return all towns within a mile radius of another postcode


