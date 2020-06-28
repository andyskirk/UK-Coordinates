# UK-Coordinates

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


