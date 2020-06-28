<?php
namespace UKPostcodes;

require "vendor/autoload.php";

use \PDO;

require_once("core/DBConfig.php");
use core\DBConfig\DBConfig;

require_once("core/functions.php");

/*
    Simply run via terminal. Change variables to change search terms
*/

// Town string to search for
$postcode = "BN1 8LP";
$distance = 10; // Number of miles radius to search for

// Form our database connection, using dbconfig class to define our settings
$dbConfig = new \DBConfig\DBConfig();
$dbh = new PDO("mysql:host={$dbConfig->server};dbname=$dbConfig->db_name", "$dbConfig->db_user", "$dbConfig->db_pass");
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// Look up the latitude/longitude values for the postcode_out
$sql = " 
    SELECT
        longitude,
        latitude
    FROM postcode_zone
    WHERE postcode_lookup = :postcode_lookup
";
// Build the search string to enable any-input postcode searching
$params = array(
    ":postcode_lookup" => createSearchString($postcode)
);
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

// The geographical location of our postcode_out (zone)
$position = $stmt->fetch(PDO::FETCH_ASSOC);

// If no results found, exit here.
if(empty($position)) {
    print("\n No Results Found\n");
    exit;
}
// Now perform a query to return the towns in a radius around the postcode_out
$sql = "
    SELECT town FROM (
        SELECT
            `postcode_out`,
            (
                3959 *
                acos(
                    cos( radians( :lat ) ) *
                    cos( radians( `latitude` ) ) *
                    cos(
                        radians( `longitude` ) - radians( :lon )
                    ) +
                    sin(radians(:lat)) *
                    sin(radians(`latitude`))
                )
            ) AS `distance`  
        FROM postcode_zone
        HAVING `distance` < :distance
        ORDER BY `distance`
    ) a
    INNER JOIN postcode_towns ON a.postcode_out = postcode_towns.district
    GROUP BY town
";

$params = array(
    ":lat" => $position['latitude'],
    ":lon" => $position['longitude'],
    ":distance" => $distance
);
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

$results = $stmt->fetchAll(PDO::FETCH_COLUMN);
tprint($results);






