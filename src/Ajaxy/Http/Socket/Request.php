<?php

namespace Ajaxy\Http\Socket;

class Request
{
    /**
     * An associative array of headers to send along with requests.
     *
     * @var array
     **/
    public $oendpoint = '';
    public $endpoint = 'tcp://localhost:5000';

    /**
     * An associative array of headers to send along with requests.
     *
     * @var array
     **/
    public $headers = array();

    /**
     * The user agent to send along with requests.
     *
     * @var string
     **/
    public $user_agent;

    /**
     * The user agent to send along with requests.
     *
     * @var string
     **/
    public $blocking = true;

    /**
     * Ignore the response.
     *
     * @var string
     **/
    public $ignore_response = false;

    /**
     * Stores an error string for the last request if one occurred.
     *
     * @var string
     **/
    protected $error = '';

    /**
     * Stores resource handle for the current CURL request.
     *
     * @var resource
     **/
    protected $request;

    /**
     * Stores the request cookie.
     *
     * @var resource
     **/
    protected $cookie_file;

    /**
     * Initializes a  object.
     **/
    public function __construct($endpoint, $headers = array())
    {
        $parsed_url = parse_url($endpoint);
        if (!$parsed_url) {
            throw new \Exception('Url must be in a valid format');
        }
        $parsed_url['host'] = isset($parsed_url['host']) && trim($parsed_url['host']) != '' ? $parsed_url['host'] : '127.0.0.1';
        $scheme = isset($parsed_url['scheme']) && trim($parsed_url['scheme']) != '' ? $parsed_url['scheme'] : 'tcp';
        if ($scheme == 'https') {
            $parsed_url['scheme'] = 'ssl';
        }
        if (!isset($parsed_url['scheme']) || $scheme == 'http' || $scheme == '') {
            $parsed_url['scheme'] = 'tcp';
        }
        if (!isset($parsed_url['port'])) {
            $parsed_url['port'] = 80;
            if ($scheme == 'https') {
                $parsed_url['port'] = '443';
            }
        }
        $this->endpoint = $parsed_url;
        $this->oendpoint = $endpoint;
        $this->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Ajaxy/Http PHP.' . PHP_VERSION;
        $this->headers = $headers;
    }

    /**
     * Makes an DELETE request to the specified $path with an optional array or string of $vars.
     *
     * Returns a Response object if the request was successful, false otherwise
     *
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response object
     **/
    public function delete($path, $vars = array())
    {
        return $this->request('DELETE', $path, $vars);
    }

    /**
     * Returns the error string of the current request if one occurred.
     *
     * @return string
     **/
    public function error()
    {
        return $this->error;
    }

    /**
     * Makes an GET request to the specified $path with an optional array or string of $vars.
     *
     * Returns a Response object if the request was successful, false otherwise
     *
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response
     **/
    public function get($path, $vars = array())
    {
        if (!empty($vars)) {
            $path .= (stripos($path, '?') !== false) ? '&' : '?';
            $path .= (is_string($vars)) ? $vars : http_build_query($vars, '', '&');
        }

        return $this->request('GET', $path);
    }

    /**
     * Makes a HEAD request to the specified $path with an optional array or string of $vars.
     *
     * Returns a Response object if the request was successful, false otherwise
     *
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response
     **/
    public function head($path, $vars = array())
    {
        return $this->request('HEAD', $path, $vars);
    }

    /**
     * Makes a POST request to the specified $path with an optional array or string of $vars.
     *
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response|bool
     **/
    public function post($path, $vars = array())
    {
        return $this->request('POST', $path, $vars);
    }

    /**
     * Makes a PUT request to the specified $path with an optional array or string of $vars.
     *
     * Returns a Response object if the request was successful, false otherwise
     *
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response|bool
     **/
    public function put($path, $vars = array())
    {
        return $this->request('PUT', $path, $vars);
    }

    /**
     * Makes a request of the specified $method to a $path with an optional array or string of $vars.
     *
     * Returns a Response object if the request was successful, false otherwise
     *
     * @param string       $method
     * @param string       $path
     * @param array|string $vars
     *
     * @return Response|bool
     **/
    public function request($method, $path = '/', $vars = array())
    {
        $errno = $errstr = '';
        $type = $this->blocking ? STREAM_CLIENT_CONNECT : STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT;

        $context = stream_context_create();

        stream_context_set_option($context, 'ssl', 'capture_peer_cert', true);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_host', false);
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);

        $socket = @stream_socket_client($this->endpoint['scheme'] . '://' . $this->endpoint['host'] . ':' . $this->endpoint['port'], $errno, $errstr, 30, $type, $context);
        if ($socket === false) {
            throw new \Exception('stream_socket_client() failed - ' . socket_strerror(socket_last_error() ? socket_last_error() : 10061) . ' - Url: ' . ($this->endpoint['scheme'] . '://' . $this->endpoint['host'] . ':' . $this->endpoint['port']) . " - Original Url: " . $this->oendpoint, socket_last_error() ? socket_last_error() : 10061);
        }

        if ($errno && $errno != '') {
            if ($socket) {
                stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
            }
            throw new \Exception(\sprintf('Error %s - %s - Parsed Url: %s - Original Url: %s', $errno, $errstr, $this->endpoint['scheme'] . '://' . $this->endpoint['host'] . ':' . $this->endpoint['port'], $this->oendpoint), $errno);
        }
        
        stream_set_blocking($socket, intval($this->blocking));
        if ($socket) {
            $content = '';
            if (is_array($vars)) {
                $content = http_build_query($vars, '', '&');
            }
            $headers = array(
                'Host' => $this->endpoint['host'] . ':' . $this->endpoint['port'],
                'Cache-Control' => 'no-cache',
                'Accept' => '*/*',
                'Content-Type' => 'application/x-www-form-urlencoded',
            );
            if (strtoupper($method) == 'POST') {
                $headers['Content-Type'] = 'application/x-www-form-urlencoded';
                $headers['Content-Length'] = strlen($content);
            }
            $headers = array_replace($headers, (array) $this->getHeaders());

            $out = "{$method} {$path} HTTP/1.1\r\n";

            foreach ($headers as $key => $header) {
                $out .= $key . ': ' . $header . "\r\n";
            }
            $out .= "\r\n";
            if (trim($content) != '') {
                $out .= $content;
            }

            while (true) {
                $read = $write = $except = array();
                $write = array($socket);
                if (false === ($num_changed_streams = stream_select($read, $write, $except, 30))) {
                } elseif ($num_changed_streams > 0) {
                    fputs($socket, $out);
                    break;
                }
            }

            if ($this->ignore_response) {
                sleep(1);
                @fclose($socket);
                return null;
            }
            $response = '';

            stream_set_timeout($socket, 10);
            if (!$this->blocking) {
                while (true) {
                    $read = $write = $except = array();
                    $read = array($socket);
                    if (false === ($num_changed_streams = stream_select($read, $write, $except, 1))) {
                    } elseif ($num_changed_streams > 0) {
                        $response .= fread($socket, 1024);
                        break;
                    }
                }
            } else {
                while (!feof($socket)) {
                    $data = fread($socket, 1024);
                    if ($data === false) {
                        break;
                    }

                    $response .= $data;
                }
            }

            if ($response != '') {
                $response = new \Ajaxy\Http\Response($response);
            }
            if ($socket) {
                fclose($socket);
            }
            return $response == '' ? null : $response;
        }

        return null;
    }

    /**
     * Gets custom headers of the current request.
     **/
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Sets whether the request will block or async.
     *
     * @since 1.0.0
     * @date  2018-01-23
     *
     * @param bool $blocking
     */
    public function setBlocking($blocking = false)
    {
        $this->blocking = $blocking;

        return $this;
    }

    /**
     * Whether to wait for response or not.
     *
     * @since 1.0.0
     * @date  2018-01-23
     *
     * @param bool $ignore
     */
    public function setIgnoreResponse($ignore = false)
    {
        $this->ignore_response = $ignore;

        return $this;
    }
}
