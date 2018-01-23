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
    public $headers = array();

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
        $pattern = '#HTTP/\d\.\d.*?$.*?\r\n\r\n#ims';

        // Extract headers from response
        preg_match_all($pattern, $response, $matches);

        $headers_string = array_pop($matches[0]);
        $headers = explode("\r\n", str_replace("\r\n\r\n", '', $headers_string));

        // Remove headers from the response body

        $this->body = str_replace($headers_string, '', $response);

        if (isset($matches[0])) {
            $_headers_string = array_pop($matches[0]);
            $this->body = str_replace($_headers_string, '', $this->body);
        }
        // Extract the version and status from the first header
        $version_and_status = array_shift($headers);
        preg_match('#HTTP/(\d\.\d)\s(\d\d\d)\s(.*)#', $version_and_status, $matches);
        $this->headers['httpVersion'] = $matches[1];
        $this->headers['statusCode'] = $matches[2];
        $this->headers['status'] = $matches[2].' '.$matches[3];

        // Convert headers into an associative array
        foreach ($headers as $header) {
            preg_match('#(.*?)\:\s(.*)#', $header, $matches);
            $this->headers[$matches[1]] = $matches[2];
        }
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
}
