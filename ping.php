<?php

define('DOCROOT', dirname(__FILE__));

function asyncCheck($urls = array())
{
    $responses = array();
    foreach($urls as $url)
    {
        $filename = 'url_' . md5($url);
        $str = file_get_contents('async.php');
        $str = str_replace(
            array('::URL::', '::FILENAME::'),
            array($url, $filename . '.result'),
            $str
        );
        file_put_contents('./var/' . $filename . '.php', $str);

        // And trigger the curl request:
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/var/' . $filename . '.php');
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        // This is the trick: the timeout ignores this curl request, but the PHP script will still run and write a file when it's done:
        curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 20);
        curl_exec($ch);
        curl_close($ch);
        echo "Made request\n";

        $responses[$filename] = false;
    }
    echo "Created array, starting async request.\n";
    // Now do async requests fool!
    $done = false;
    while(!$done)
    {
        foreach($urls as $url) {
            $filename = 'url_' . md5($url);

            if($responses[$filename] == false)
            {
                if(file_exists('./var/' . $filename . '.result')) {
                    $responses[$filename] = unserialize(file_get_contents('./var/' . $filename . '.result'));
                }
            }
        }
        echo round((count($responses) / count($urls)) * 100) . '%' . "\n";

        // Check if done:
        $done = true;
        foreach($responses as $k => $v) {
            if($v == false) {
                echo $k . ' = false' . "\n";
                $done = false;
            }
        }

        // Sleepy time:
        sleep(1);
    }

    return $responses;
}

if ($argc == 2) {
    $url = $argv[1];
    $urls = array();
    if(substr($url, -4, 4) == '.csv')
    {
        // Get the number of lines:
        $file = fopen($url, "r");
        while(!feof($file)){
            $line = fgetcsv($file);
            if(!empty($line[0]) && $line[0] != 'site')
            {
                $urls[] = $line[0];
            }
        }
        fclose($file);

        // Read it all:
        $csv  = array(
            array("url","success","message","ip","www","time_first","time_total","code","size","redirect_url","redirect_from")
        );
        echo "Checking " . count($urls) . " URL's: \n";
        $files = glob('./var/*');
        foreach($files as $f) { unlink($f); }
        asyncCheck($urls);
        echo "Done!   \n";
        $file = fopen(DOCROOT . '/result.csv', 'w');
        foreach($csv as $line) {
            fputcsv($file, $line);
        }
        fclose($file);
    } else {
        $result = asyncCheck(array($url));
        print_r($result);
    }
} else {
    echo "Usage:

php ping.php [url]
php ping.php [csv]
\n";
}
