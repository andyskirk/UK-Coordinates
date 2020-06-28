<?php
// Pretty data output for debugging
function tprint($ary, $die = true) {
    print("<pre>");
    print_r($ary);
    print("</pre>");

    if ($die) {
        die("\nStopped.\n");
    }
}

// Create a search string (remove non alphanumerics, upper case and limit to 15 chars)
function createSearchString($source) {
    $search = strtoupper(preg_replace("/[^a-z0-9]+/i", '', $source));

    if (strlen($search) > 15)
        $search = substr($search,0, 15);

    return $search;
}