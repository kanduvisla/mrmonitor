<?php
/**
 * Project: Mr. Monitor.
 * User:    gielberkers
 * Date:    17/07/14
 */

// Read sites
$handle = fopen('_sites.csv', 'r');

// Create CSV file:
$csv = array("url","success","message","ip","www","time_first","time_total","code","size","redirect_url","redirect_from");
file_put_contents('results.csv.tmp', '"' . implode('","', $csv) . '"' . "\n");
file_put_contents('counter.tmp', 0);

$urls = array();

// Iterate through the rows:
while($row = fgetcsv($handle))
{
    $url = $row[0];
    if(!is_null($url))
    {
        $urls[] = $url;
    }
}

foreach($urls as $url)
{
    // Start poort man's asynchronous request:
    echo "Start async request for " . $url . "\n";
    sleep(1);
    shell_exec('php single.php ' . $url . ' ' . count($urls) . ' > /dev/null &');
}