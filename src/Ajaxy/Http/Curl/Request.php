<?php

namespace Ajaxy\Http\Curl;
/**
 * Basic CURL wrapper
 *
 * See the README for documentation/examples or http://php.net/curl for more information about the libcurl extension for PHP
 *
 * @package curl
**/
class Request {

    /**
     * Determines whether or not requests should follow redirects
     *
     * @var boolean
    **/
    public $follow_redirects = true;

    /**
     * An associative array of headers to send along with requests
     *
     * @var array
    **/
    public $headers = array();

    /**
     * An associative array of CURLOPT options to send along with requests
     *
     * @var array
    **/
    public $options = array();

    /**
     * The referer header to send along with requests
     *
     * @var string
    **/
    public $referer;

    /**
     * Curl verbose mode
     *
     * @var string
    **/
    public $verbose;

    /**
     * The user agent to send along with requests
     *
     * @var string
    **/
    public $user_agent;

    /**
     * Stores an error string for the last request if one occurred
     *
     * @var string
     * @access protected
    **/
    protected $error = '';

    /**
     * Stores resource handle for the current CURL request
     *
     * @var resource
     * @access protected
    **/
    protected $request;

    /**
     * Stores the request cookie
     *
     * @var resource
     * @access protected
    **/
    protected $cookie_file;

    /**
     * Initializes a Curl object
    **/
    function __construct($headers = array()) {
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Ajaxy/Curl PHP.'.PHP_VERSION;
        $this->headers = $headers;
    }

    /**
     * Makes an HTTP DELETE request to the specified $url with an optional array or string of $vars
     *
     * Returns a Ajaxy\Http\Response object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response object
    **/
    function delete($url, $vars = array()) {
        return $this->request('DELETE', $url, $vars);
    }

    /**
     * Returns the error string of the current request if one occurred
     *
     * @return string
    **/
    function error() {
        return $this->error;
    }

    /**
     * Makes an HTTP GET request to the specified $url with an optional array or string of $vars
     *
     * Returns a Ajaxy\Http\Response object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response
    **/
    function get($url, $vars = array()) {
        if (!empty($vars)) {
            $url .= (stripos($url, '?') !== false) ? '&' : '?';
            $url .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }
        return $this->request('GET', $url);
    }

    /**
     * Makes an HTTP HEAD request to the specified $url with an optional array or string of $vars
     *
     * Returns a Ajaxy\Http\Response object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response
    **/
    function head($url, $vars = array()) {
        return $this->request('HEAD', $url, $vars);
    }

    /**
     * Makes an HTTP POST request to the specified $url with an optional array or string of $vars
     *
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response|boolean
    **/
    function post($url, $vars = array()) {
        return $this->request('POST', $url, $vars);
    }

    /**
     * Makes an HTTP PUT request to the specified $url with an optional array or string of $vars
     *
     * Returns a Ajaxy\Http\Response object if the request was successful, false otherwise
     *
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response|boolean
    **/
    function put($url, $vars = array()) {
        return $this->request('PUT', $url, $vars);
    }

    /**
     * Makes an HTTP request of the specified $method to a $url with an optional array or string of $vars
     *
     * Returns a Ajaxy\Http\Response object if the request was successful, false otherwise
     *
     * @param string $method
     * @param string $url
     * @param array|string $vars
     * @return Ajaxy\Http\Response|boolean
    **/
    function request($method, $url, $vars = array()) {
        $this->error = '';
        $this->request = curl_init($url);

        if (is_array($vars)) $vars = http_build_query($vars, '', '&');

        $this->set_request_method($method);
        $this->set_request_options($url, $vars);
        $this->set_request_headers();

        $response = curl_exec($this->request);

        if ($response) {
            $response = new \Ajaxy\Http\Response($response);
        } else {
            $this->error = curl_errno($this->request).' - '.curl_error($this->request);
        }
        curl_close($this->request);

        return $response;
    }

    /**
     * Formats and adds custom headers to the current request
     *
     * @return void
     * @access protected
    **/
    protected function set_request_headers() {
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key.': '.$value;
        }
        curl_setopt($this->request, CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set the associated CURL options for a request method
     *
     * @param string $method
     * @return void
     * @access protected
    **/
    protected function set_request_method($method) {
        switch (strtoupper($method)) {
            case 'HEAD':
                curl_setopt($this->request, CURLOPT_NOBODY, true);
                break;
            case 'GET':
                curl_setopt($this->request, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($this->request, CURLOPT_POST, true);
                break;
            default:
                curl_setopt($this->request, CURLOPT_CUSTOMREQUEST, $method);
        }
    }

    /**
     * Get the value of Follow Redirects
     *
     * @return mixed
     */
    public function getFollowRedirects()
    {
        return $this->follow_redirects;
    }

    /**
     * Set the value of Follow Redirects
     *
     * @param mixed $follow_redirects
     *
     * @return self
     */
    public function setFollowRedirects($follow_redirects)
    {
        $this->follow_redirects = $follow_redirects;

        return $this;
    }

    /**
     * Get the value of Referer
     *
     * @return mixed
     */
    public function getReferer()
    {
        return $this->referer;
    }

    /**
     * Set the value of Referer
     *
     * @param mixed $referer
     *
     * @return self
     */
    public function setReferer($referer)
    {
        $this->referer = $referer;

        return $this;
    }

    /**
     * Get the value of User Agent
     *
     * @return mixed
     */
    public function getUserAgent()
    {
        return $this->user_agent;
    }

    /**
     * Set the value of User Agent
     *
     * @param mixed $user_agent
     *
     * @return self
     */
    public function setUserAgent($user_agent)
    {
        $this->user_agent = $user_agent;

        return $this;
    }

    /**
     * Get the value of Cookie File
     *
     * @return mixed
     */
    public function getCookieFile()
    {
        return $this->cookie_file;
    }

    /**
     * Set the value of Cookie File
     *
     * @param mixed $cookie_file
     *
     * @return self
     */
    public function setCookieFile($cookie_file)
    {
        $this->cookie_file = $cookie_file;

        return $this;
    }
    /**
     * Sets the CURLOPT options for the current request
     *
     * @param string $url
     * @param string $vars
     * @return void
     * @access protected
    **/
    protected function set_request_options($url, $vars) {
        curl_setopt($this->request, CURLOPT_URL, $url);

        if (!empty($vars)) curl_setopt($this->request, CURLOPT_POSTFIELDS, $vars);

        # Set some default CURL options
        curl_setopt($this->request, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($this->request, CURLOPT_HEADER, true);
        curl_setopt($this->request, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->request, CURLOPT_USERAGENT, $this->user_agent);
        if ($this->verbose) {
            curl_setopt($this->request, CURLOPT_VERBOSE, true);
        }
        if ($this->cookie_file) {
            curl_setopt($this->request, CURLOPT_COOKIEFILE, $this->cookie_file);
            curl_setopt($this->request, CURLOPT_COOKIEJAR, $this->cookie_file);
        }
        if ($this->follow_redirects) curl_setopt($this->request, CURLOPT_FOLLOWLOCATION, true);
        if ($this->referer) curl_setopt($this->request, CURLOPT_REFERER, $this->referer);

        # Set any custom CURL options
        foreach ($this->options as $option => $value) {
            curl_setopt($this->request, constant('CURLOPT_'.str_replace('CURLOPT_', '', strtoupper($option))), $value);
        }
    }
}
