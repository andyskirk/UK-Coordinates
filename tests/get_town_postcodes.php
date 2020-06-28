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
$town_name = "uckfield";

// Form our database connection, using dbconfig class to define our settings
$dbConfig = new \DBConfig\DBConfig();
$dbh = new PDO("mysql:host={$dbConfig->server};dbname=$dbConfig->db_name", "$dbConfig->db_user", "$dbConfig->db_pass");
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Perform the sql lookup
$sql = "
    SELECT
        postcode_zone.postcode
    FROM postcode_towns
    INNER JOIN postcode_zone ON postcode_towns.district = postcode_zone.postcode_out
    WHERE LOWER(postcode_towns.town) = :town_name 
";
$params = array(":town_name" => strtolower($town_name));
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

// Dump Data
tprint($stmt->fetchAll(PDO::FETCH_ASSOC));





