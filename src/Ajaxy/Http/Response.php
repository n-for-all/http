<?php

namespace Ajaxy\Http;

/**
 * Parses the response from a request into an object containing
 * the response body and an associative array of headers.
 **/
class Response implements \JsonSerializable
{
    /**
     * The body of the response without the headers block.
     *
     * @var string
     **/
    public $body = '';

    /**
     * An associative array containing the response's headers.
     *
     * @var array
     */
    public $headers = array(
        'httpVersion' => '',
        'statusCode' => '',
        'status' => ''
    );

    /**
     * Parses the response retrieved from the request
     *
     * @since 1.0.0
     * @date  2018-01-23
     * @param Curl or Socket     $response
     */
    public function __construct($response)
    {
        // Headers regex
        $pattern = '#HTTP/(\d\.\d|\d).*?$.*?#ims';

        // Extract headers from response
        preg_match_all($pattern, $response, $matches);

        list($headers, $body) = explode("\r\n\r\n", $response, 2);

        $headers_string = trim(preg_replace($pattern, '', $headers));
        $parsedHeaders = $this->parseHeaders($headers_string);

        // Remove headers from the response body

        $this->body = trim(str_replace($headers, '', $response));

        // Extract the version and status from the first header
        preg_match('#HTTP/(\d\.\d|\d)\s(\d\d\d)\s(.*)#', $response, $matches);
        if($matches && count($matches) >= 3){
            $this->headers['httpVersion'] = $matches[1];
            $this->headers['statusCode'] = $matches[2];
            $this->headers['status'] = $matches[2].' '.$matches[3];
        }

        // Convert headers into an associative array
        $this->headers = $parsedHeaders;
    }

    /**
     * Returns the response body as string.
     *
     * @since  1.0.0
     * @date   2018-01-23
     *
     * @return string the response body
     */
    public function __toString()
    {
        return $this->body;
    }

    /**
     * Decodes the body, helper mothod if the reponse is json.
     *
     * @since  1.0.0
     * @date   2018-01-23
     *
     * @return object
     */
    public function decode()
    {
        return json_decode(trim($this->body), true);
    }

    /**
     * Get the reponse headers.
     *
     * @since  1.0.0
     * @date   2018-01-23
     *
     * @return array of key value pari
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the status code.
     *
     * @since  1.0.0
     * @date   2018-01-23
     *
     * @return string
     */
    public function getStatusCode()
    {
        return $this->headers['statusCode'];
    }

    /**
     * Serialize the class to json.
     *
     * @since  1.0.0
     * @date   2018-01-23
     *
     * @return array of headers and body
     */
    public function jsonSerialize()
    {
        return array('headers' => $this->headers, 'body' => $this->body);
    }

    /**
     * Parse http headers.
     *
     * @since  1.0.3
     * @date   2019-12-10
     *
     * @return array of headers and body
     */
    public function parseHeaders( $headers ) {
        if( function_exists( 'http_parse_headers' ) ) {
            return \http_parse_headers($headers);
        }
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $headers));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) && $field != '') {
                $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./', function($matches) {return strtoupper($matches[0]);}, strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    if ( is_array( $retVal[$match[1]] ) ) {
                        $i = count($retVal[$match[1]]);
                        $retVal[$match[1]][$i] = $match[2];
                    }
                    else {
                        $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                    }
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }else{
                break;
            }
        }
        return $retVal;
    }
}
