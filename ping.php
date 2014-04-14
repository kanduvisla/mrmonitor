<?php

define('DOCROOT', dirname(__FILE__));

/**
 * Ping a URL
 *
 * @param $url
 * @return string|bool
 */
function ping($url)
{
    $url = strtolower($url);

    // Get Curl information:
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    $info = curl_getinfo($ch);
    $info = array(
        'ip' => $info['primary_ip'],
        'www' => substr($url, 0, 4) == 'www.',
        'time_first' => $info['pretransfer_time'],
        'time_total' => $info['total_time'],
        'code' => $info['http_code'],
        'size' => $info['size_download']
    );

    return $info;
}

/**
 * Check URL
 *
 * @param $url
 * @return array
 */
function check_url($url)
{
    $result = array(
        'url' => $url,
        'success' => 0,
        'message' => ''
    );

    // Check if http://
    if (substr($url, 0, 7) == 'http://') {
        $url = str_replace('http://', '', $url);

        // Check if www:
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
        }

        // Check non-www:
        $resultNonWww = ping($url);

        // Check www:
        $resultWithWww = ping('www.' . $url);

        $redirectCodes = array(301, 302);

        // Check for redirect:
        if(
            (in_array($resultWithWww['code'], $redirectCodes) && $resultNonWww['code'] == 200) ||
            ($resultWithWww['code'] == 200 && in_array($resultNonWww['code'], $redirectCodes))
        ) {
            // Redirect is correct.
            $siteResult = $resultWithWww['code'] == 200 ? $resultWithWww : $resultNonWww;

            $result = array_merge($result, $siteResult);
            $result['www'] = $result['www'] ? 1 : 0;

            // Check size:
            if($siteResult['size'] < 1024) {
                $result['message'] = 'Site is less than 1kb. Something must be wrong!';
            } else {
                $result['success'] = 1;
                $result['message'] = 'Ok';
            }
        } else {
            $result['message'] = 'Www and non/www don\'t return 200/301/302';
        }
    } else {
        $result['message'] = 'URL must start with http://';
    }

    return $result;
}

if ($argc == 2) {
    $url = $argv[1];
    if(substr($url, -4, 4) == '.csv')
    {
        // Get the number of lines:
        $linecount = 0;
        $currentLine = 0;
        $file = fopen($url, "r");
        while(!feof($file)){
          $line = fgets($file);
          $linecount++;
        }
        fclose($file);

        // Read it all:
        $file = fopen($url, 'r');
        $csv  = array(
            array("url","success","message","ip","www","time_first","time_total","code","size")
        );
        echo "Checking " . ($linecount - 1) . " URL's: \n";
        while($line = fgetcsv($file))
        {
            if($line[0] != 'site') {
                $result = check_url($line[0]);
                $csv[] = array_values($result);
            }
            $currentLine ++;
            $p = round(($currentLine / $linecount) * 100);
            echo $p . "%   \r";
        }
        echo "Done!   \n";
        fclose($file);
        $file = fopen(DOCROOT . '/result.csv', 'w');
        foreach($csv as $line) {
            fputcsv($file, $line);
        }
        fclose($file);
    } else {
        $result = check_url($url);
        print_r($result);
    }
} else {
    echo "Usage:

php ping.php [url]
php ping.php [csv]
\n";
}
