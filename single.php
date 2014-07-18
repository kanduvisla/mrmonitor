<?php
/**
 * Project: Mr. Monitor.
 * User:    gielberkers
 * Date:    17/07/14
 */

if($argc == 1) {
    echo "Usage: $ php single.php [url]\n";
    die;
}

define('DIRNAME', dirname(__FILE__));

$url = $argv[1];

// Test the site:
require(DIRNAME . '/new/Site.php');

$site = new Site($url);
$results = $site->getTestResults();

// Check if this was the last test:
if($argc == 3) {
    file_put_contents(DIRNAME . '/results.csv.tmp', '"' . implode('","', $results) . '"' . "\n", FILE_APPEND);

    $count = $argv[2];
    $total = file_get_contents(DIRNAME . '/counter.tmp');
    $total ++;
    if($total == $count) {
        // This was the last one
        unlink(DIRNAME . '/counter.tmp');
        if(file_exists(DIRNAME . '/results.csv'))
        {
            unlink(DIRNAME . '/results.csv');
        }
        rename(DIRNAME . '/results.csv.tmp', DIRNAME . '/results.csv');
    } else {
        file_put_contents(DIRNAME . '/counter.tmp', $total);
    }
} else {
    var_dump($results);
}
