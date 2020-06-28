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