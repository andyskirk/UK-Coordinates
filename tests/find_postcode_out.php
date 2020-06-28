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

// Postcode_out string to search for
$postcode_out = "BN1";

// Form our database connection, using dbconfig class to define our settings
$dbConfig = new \DBConfig\DBConfig();
$dbh = new PDO("mysql:host={$dbConfig->server};dbname=$dbConfig->db_name", "$dbConfig->db_user", "$dbConfig->db_pass");
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


// Perform the sql lookup
$sql = "
    SELECT
        postcode_towns.town
    FROM postcode_towns
    WHERE UPPER(postcode_towns.district) = :postcode_out 
";
$params = array(":postcode_out" => strtolower($postcode_out));
$stmt = $dbh->prepare($sql);
$stmt->execute($params);

// Dump data
tprint($stmt->fetch(PDO::FETCH_ASSOC));





