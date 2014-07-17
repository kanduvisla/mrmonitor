<?php
/**
 * Project: Mr. Monitor.
 * User:    gielberkers
 * Date:    17/07/14
 */

class Site
{
    private $_url;
    private $_info;
    private $_tests;

    const MAX_REDIRECTS = 10;
    const MAX_TIME_FIRST = 0.500;

    /**
     * Constructor
     *
     * @param $urlString
     */
    public function __construct($urlString)
    {
        $this->_url   = trim(strtolower($urlString));
        $this->_info  = $this->getInfo(true);

        // Prepare array to hold the test results:
        $this->_tests = array(
            'url'           => $this->_url,
            'success'       => 1,
            'message'       => '',
            'ip'            => '',
            'www'           => 1,
            'time_first'    => '',
            'time_total'    => '',
            'code'          => '',
            'redirect_from' => $this->_url,
            'redirect_to'   => ''
        );

        // First validate the redirects:
        $this->validateRedirect();

        // Validate the status code:
        $this->validateCode();

        // Validate speed:
        $this->validateSpeed();
    }

    /**
     * Get the test results
     *
     * @return array
     */
    public function getTestResults()
    {
        return $this->_tests;
    }

    /**
     * Validate speed
     */
    private function validateSpeed()
    {
        if($this->_info['time_first'] > self::MAX_TIME_FIRST)
        {
            $this->addTestMessage("Time to first byte is longer than " . self::MAX_TIME_FIRST . " seconds");
            if($this->_tests['success'] != 0) {
                $this->_tests['success'] = 2;
            }
        }
        $this->_tests['time_first'] = $this->_info['time_first'];
        $this->_tests['time_total'] = $this->_info['time_total'];
    }

    /**
     * Validate HTTP response code
     */
    private function validateCode()
    {
        $this->_tests['code'] = $this->_info['code'];
        if($this->_info['code'] != 200)
        {
            echo '[FAIL] status code is not 200: ' . $this->_info['code'] . "\n";
            $this->_tests['success'] = 0;
            if($this->_info['code'] == 0) {
                $this->addTestMessage('Domain does not exist');
            }
        }
    }

    /**
     * Validate redirects for www/non-www
     */
    private function validateRedirect()
    {
        if(preg_match('/www\./', $this->_url)) {
            echo "check redirects for www domains ...\n";
            // Check if www-domain redirects to non-www or the other way around:
            if($this->isRedirect())
            {
                echo "This domain has a redirect, following ...\n";
                $this->getFinalRedirect();
            } else {
                // Check if the non-www url is the redirect then:
                echo "This domain has no redirect, checking non-www domain ...\n";
                $info = $this->getInfo(true, preg_replace('/www\./', '', $this->_url));
                if($this->isRedirect($info) && trim($info['redirect_url'], '/') == $this->_url)
                {
                    echo "[PASS] non-www has redirect\n";
                } else {
                    echo "[FAIL] non-www has no redirect\n";
                    $this->_tests['www'] = 0;
                    $this->addTestMessage('Non-www has no redirect to www');
                    if($this->_tests['success'] != 0) {
                        $this->_tests['success'] = 2;
                    }
                }
            }
        }
        // Set test data:
        $this->_tests['redirect_to'] = $this->_url;
        $this->_tests['ip'] = $this->_info['ip'];
    }

    private function addTestMessage($msg)
    {
        $this->_tests['message'] .= $msg . "||";
    }

    /**
     * Check whether the URL is www or not
     *
     * @param string $url
     * @return bool
     */
    public function isWww($url = null)
    {
        if($url == null)
        {
            $url = $this->_url;
        }
        return preg_match('/www\./', $url) == 1;
    }

    /**
     * Check if this request is a redirect
     *
     * @param array $info
     * @return bool
     */
    private function isRedirect($info = null)
    {
        if($info == null)
        {
            $info = $this->_info;
        }
        return in_array($info['code'], array(301, 302));
    }

    /**
     * Follow the redirect until there is nothing to redirect anymore...
     */
    private function getFinalRedirect()
    {
        $count = 0;
        while($this->isRedirect() && $count < self::MAX_REDIRECTS)
        {
            $this->_url  = $this->_info['redirect_url'];
            $this->_info = $this->getInfo(true);
            $count ++;
        }
    }

    /**
     * Get info from a URL
     *
     * @param boolean $headerOnly
     * @param string $url
     * @return string|bool
     */
    private function getInfo($headerOnly = false, $url = null)
    {
        if($url == null)
        {
            $url = $this->_url;
        }
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
            'www' => $this->isWww($url),
            'time_first' => $info['pretransfer_time'],
            'time_total' => $info['total_time'],
            'code' => $info['http_code'],
            'size' => $info['size_download'],
            'redirect_url' => strtolower($info['redirect_url'])
        );
        return $info;
    }
}