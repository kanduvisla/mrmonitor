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

$url = $argv[1];

// Test the site:
require('new/Site.php');

$site = new Site($url);
$results = $site->getTestResults();

// Check if this was the last test:
if($argc == 3) {
    file_put_contents('results.csv.tmp', '"' . implode('","', $results) . '"' . "\n", FILE_APPEND);

    $count = $argv[2];
    $total = file_get_contents('counter.tmp');
    $total ++;
    if($total == $count) {
        // This was the last one
        unlink('counter.tmp');
        if(file_exists('results.csv'))
        {
            unlink('results.csv');
        }
        rename('results.csv.tmp', 'results.csv');
    } else {
        file_put_contents('counter.tmp', $total);
    }
} else {
    var_dump($results);
}
