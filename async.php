<?php
/**
 * Ping a URL
 *
 * @param $url
 * @param $headerOnly
 * @return string|bool
 */
function ping($url, $headerOnly = false)
{
    $url = strtolower($url);

    // Get Curl information:
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if($headerOnly) {
        curl_setopt($ch, CURLOPT_HEADER, true);
    }
    curl_exec($ch);
    $info = curl_getinfo($ch);
    $info = array(
        'ip' => (isset($info['primary_ip']) ? $info['primary_ip'] : ''),
        'www' => substr($url, 0, 4) == 'www.',
        'time_first' => $info['pretransfer_time'],
        'time_total' => $info['total_time'],
        'code' => $info['http_code'],
        'size' => $info['size_download'],
        'redirect_url' => strtolower($info['redirect_url'])
    );
    return $info;
}

/**
 * Small function to follow redirects
 *
 * @param $url
 * @return array
 */
function followRedirects($url)
{
    $path = array($url);
    $info = ping($url);
    $count = 0;
    while(($info['code'] == 301 || $info['code'] == 302) && $count < 10)
    {
        $path[] = $info['redirect_url'];
        $info = ping($info['redirect_url']);
        $count ++;
    }
    return array(
        'path' => $path,
        'info' => $info,
        'count' => $count
    );
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
        'message' => '',
        'ip' => '',
        'www' => false,
        'time_first' => '',
        'time_total' => '',
        'code' => '',
        'size' => '',
        'redirect_url' => '',
        'redirect_from' => ''
    );

    // Check if http://
    if (substr($url, 0, 7) == 'http://') {
        $url = str_replace('http://', '', $url);

        // Check if www:
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
            $isWww = true;
        } else {
            $isWww = false;
        }

        // Check non-www:
        $resultNonWww = ping($url);

        // Check www:
        if($isWww)
        {
            $resultWithWww = ping('www.' . $url);
        } else {
            $resultWithWww = $resultNonWww;
        }

        $redirectCodes = array(301, 302);

        // Check if both exist:
        if($resultNonWww['code'] == 0 || $resultWithWww['code'] == 0)
        {

            // Either the www or non-www domain doesn't exist.
            $result['message'] = ($resultNonWww['code'] == 0 ? 'non-www ' : 'www ') . 'url doesn\'t exist.';
            $result['success'] = 2;

        } else {

            if($resultNonWww['code'] != 200 && $resultWithWww['code'] != 200)
            {
                // Both domains don't redirect to a page, check where they redirect to:
                if(in_array($resultWithWww['code'], $redirectCodes) && !empty($resultWithWww['redirect_url']))
                {
                    $info = followRedirects($resultWithWww['redirect_url']);
                    $resultWithWww = $info['info'];
                    $resultWithWww['redirect_from'] = implode(' &gt; ', $info['path']);
                }

                if(in_array($resultNonWww['code'], $redirectCodes) && !empty($resultNonWww['redirect_url']))
                {
                    $info = followRedirects($resultNonWww['redirect_url']);
                    $resultNonWww = $info['info'];
                    $resultNonWww['redirect_from'] = implode(' &gt; ', $info['path']);
                }
            }

            // Check for 200 code:
            if($resultNonWww['code'] == 200 || $resultWithWww['code'] == 200)
            {

                // At least one of the two is correct:
                if($resultNonWww['code'] != $resultWithWww['code'] || !$isWww)
                {

                    // Check for redirect:
                    if(
                        (in_array($resultWithWww['code'], $redirectCodes) && $resultNonWww['code'] == 200) ||
                        ($resultWithWww['code'] == 200 && in_array($resultNonWww['code'], $redirectCodes)) ||
                        !$isWww
                    ) {
                        // Redirect is correct (or is !$isWww).
                        // Get the site that has the 200 code:
                        $siteResult = $resultWithWww['code'] == 200 ? $resultWithWww : $resultNonWww;

                        $result = array_merge($result, $siteResult);
                        $result['www'] = $result['www'] ? 1 : 0;

                        // Check size:
                        if($siteResult['size'] < 1024) {

                            // Site is too small:
                            $result['message'] = 'Site is less than 1kb. Something must be wrong!';

                        } else {

                            // All cool:
                            $result['success'] = 1;
                            $result['message'] = 'Ok';
                        }
                    } else {

                        // Error!
                        $result['message'] = 'Www and non/www don\'t return 200/301/302';
                        $result = array_merge($result, $resultNonWww);
                    }

                } else {

                    // Duplicate content
                    $result['message'] = 'Both www and non-www return 200. One should be a 301/302 to the other';
                    $result['success'] = 2;
                }

            } else {

                // Error!
                $result['message'] = 'No 200 response code detected: www=' . $resultWithWww['code'].', non-www=' . $resultNonWww['code'];

            }

        }

    } else {

        $result['message'] = 'URL must start with http:// (' . $url . ')';
        $result['success'] = 2;

    }

    return $result;
}

$result = ping('::URL::');
file_put_contents('./::FILENAME::', serialize($result));