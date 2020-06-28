<?php




namespace PHPCoord;

require "vendor/autoload.php";

use \PDO;

require_once("DBConfig.php");
use \core\DBConfig\DBConfig;

require_once("functions.php");

/*
USAGE:
    Install PHPCoord (https://github.com/dvdoug/PHPCoord)
    Load OS-data csv's in /data (CodePoint Open) - (https://www.ordnancesurvey.co.uk/opendatadownload/products.html#CODEPO)
    Set database configs in core/DBconfig
    Run core/database.sql to create tables
    This may take a few minutes to run depending on your configuration
*/

try {
    $dbConfig = new DBConfig();

    $dbh = new PDO("mysql:host={$dbConfig->server};dbname=$dbConfig->db_name", "$dbConfig->db_user", "$dbConfig->db_pass");

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Function to output the first part of a uk postcode
    function getUKPostcodeFirstPart($postcode) {
        // validate input parameters
        $postcode = strtoupper($postcode);

        // UK mainland / Channel Islands (simplified version, since we do not require to validate it)
        if (preg_match('/^[A-Z]([A-Z]?\d(\d|[A-Z])?|\d[A-Z]?)\s*?\d[A-Z][A-Z]$/i', $postcode))
            return preg_replace('/^([A-Z]([A-Z]?\d(\d|[A-Z])?|\d[A-Z]?))\s*?(\d[A-Z][A-Z])$/i', '$1', $postcode);
        // British Forces
        if (preg_match('/^(BFPO)\s*?(\d{1,4})$/i', $postcode))
            return preg_replace('/^(BFPO)\s*?(\d{1,4})$/i', '$1', $postcode);
        // overseas territories
        if (preg_match('/^(ASCN|BBND|BIQQ|FIQQ|PCRN|SIQQ|STHL|TDCU|TKCA)\s*?(1ZZ)$/i', $postcode))
            return preg_replace('/^([A-Z]{4})\s*?(1ZZ)$/i', '$1', $postcode);

        // well ... even other form of postcode... return it as is
        return $postcode;
    }

    // Load the CSV's and insert into the database
    $sql = "INSERT INTO postcodes VALUES (:postcode, :east, :north, :admin_county_code, :admin_district_code, :admin_ward_code)";
    $stmt = $dbh->prepare($sql);
    $row_count = 0; // Tells us how many rows of data processed
    foreach (scandir(__DIR__ . "/data") as $csv_filename) {
        // Filter out directories found by scandir
        if (in_array($csv_filename, array(".", "..")))
            continue;

        $filename = __DIR__ . "/data/" . $csv_filename;
        $csv_file_resource = fopen($filename, "r");

        $params = array();
        $sql_insert_fields = array();
        while (($data = fgetcsv($csv_file_resource)) !== false) {
            $params[":postcode"] = $data[0];
            $params[":east"] = $data[2];
            $params[":north"] = $data[3];
            $params[":admin_county_code"] = $data[7];
            $params[":admin_district_code"] = $data[8];
            $params[":admin_ward_code"] = $data[9];
            $stmt->execute($params);

            $row_count++; // Increment the row counter
        }
    }

    // Generator function to load data from the processed `postcodes` table
    function generate_postcodes(PDO $dbh){
        // Load the postcodes
        $sql = " SELECT * FROM postcodes";
        $stmt = $dbh->prepare($sql);
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    // Create the longitude/latitude from the UTM (easterly/northerly) records from OS Opendata
    $rows_processed = 0; // Count many rows have we processed.
    $rows_remaining = $row_count - 1; // Count remaining to process. -1 to account for 0-indexing.
    $params = array(); // MySQL Params array
    $insert_strings = array(); // Array of insert strings
    $lat_rows = 0;
    // Use our generator function to process the rows.
    foreach (generate_postcodes($dbh) as $postcode) {

        // Set an array of wildcard bindings per iteration
        $insert_strings[] = "(?,?,?,?)";

        // Use PHPCoord\OSRef to convert the easterly/northenly values to latitude/longitude values
        $OSRef = new \PHPCoord\OSRef(intval($postcode['east']), intval($postcode['north']));
        $LatLng = $OSRef->toLatLng();

        // Add the values for this iteration to the params array
        array_push($params,
            $postcode['postcode'],
            getUKPostcodeFirstPart($postcode['postcode']),
            $LatLng->getLat(),
            $LatLng->getLng()
        );

        // If we hit 10,000 rows processed (or we are in our last batch and will never reach 10,000), execute mysql insert
        if($rows_processed === 10000 || $rows_remaining === 0) {
            // Implode the insert strings to make mysql insert/values statements
            $value_placeholders = implode(",\n", $insert_strings);

            // Declare sql and add placeholders string
            $sql = " INSERT INTO postcode_zone VALUES $value_placeholders;";
            $stmt = $dbh->prepare($sql);

            $stmt->execute($params);
            $rows_processed = 0; // Reset the rows_processed
            $params = array(); // Reset the params array
            $insert_strings = array(); // Reset the insert strings array
        }

        // We have processed a row, so decrement the rows remaining
        $rows_remaining--;
        $rows_processed++;
        $lat_rows++;
    }

    // Now scrape the postcode town names from wikipedia
    $wiki_dom = file_get_contents("https://en.wikipedia.org/wiki/List_of_postcode_districts_in_the_United_Kingdom");
    @$domDocument = new \DOMDocument();
    @$domDocument->loadHTML($wiki_dom);
    $domDocument->preserveWhiteSpace = false;
    $domDocument->formatOutput = true;
    $domTable = $domDocument->getElementsByTagName("table");
    $domRows = $domTable->item(1)->getElementsByTagName("tr");

    // The sql we'll use to insert each postcode_district name
    $postcode_insert_sql = "
        INSERT IGNORE INTO postcode_towns VALUES (:area, :district, :town, :county);
    ";
    // Begin the mysql connection.
    $stmt = $dbh->prepare($postcode_insert_sql);

    $i = 0;
    $row_counter = 0;
    // Iterate on our table rows to pull out the town/postcode definitions
    foreach ($domRows as $row) {
        if ($i === 0) {
            $i++;
            continue;
        }

        $cols = $row->childNodes;
        // Number account for domdocument::childNodes weirdness.
        $wanted_columns = array(
            "postcode_area" => trim($cols->item(1)->textContent),
            "postcode_district" => trim($cols->item(3)->textContent),
            "postcode_town" => trim($cols->item(5)->textContent),
            "postcode_county" => trim($cols->item(7)->textContent)
        );

        // ['postcode_district'] can be comma separated. Iterate on an explode.
        $wanted_columns['postcode_district'] = str_replace("\n", "", $wanted_columns['postcode_district']);
        $districts = explode(", ", $wanted_columns['postcode_district']);
        // Remove hidden fields from wikipedia.
        foreach ($districts as $key => $value) {
            $exp = explode(" ", $value);
            if (count($exp) > 1) {
                $districts[$key] = $exp[1];
            }
        }


        foreach ($districts as $dist) {
            // Remove unwanted strings

            $string_replace_array = array(
                "shared", "non-geo", "[1]", "[2]", "[3]", "[4]", "[5]", "[6]", "[7]", "[8]", "[9]", "[10]", ",", "varies", "(", ")"
            );

            $params = array(
                ":area" => $wanted_columns['postcode_area'],
                ":district" => trim(str_replace($string_replace_array, "", $dist)),
                ":town" => $wanted_columns['postcode_town'],
                ":county" => trim(str_replace($string_replace_array, "", $wanted_columns['postcode_county']))
            );

            $stmt->execute($params);
            $row_counter++;
        }
    }

    print("Success!\n");
    print("OSData Rows added: $row_count\n");
    print("Lat/Long Rows added: $lat_rows\n");
    print("Postcode Areas added: $row_counter\n");


} catch (PDOException $e) {
    tprint($e);
} catch (Exception $e) {
    tprint($e);
}

