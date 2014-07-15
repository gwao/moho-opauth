<?php
/**
 * Class Client
 * @author Zheng Xian Qiu
 */

namespace MOHO\API;

class Client
{
    const API_BASE_URL = "https://magic-bonus.com/api/v3";
    const API_DEBUG_URL = "http://sandbox.moho.com.tw/api/v3";

    protected $type = null;
    protected $token = null;
    protected $refresh_token = null;
    protected $client_id = null;
    protected $client_secret = null;
    protected $debug_mode = false;

    private $api = null;

    /**
     * This client is based on Opauth
     */

    public function __construct($opauth, $is_debug = false)
    {
        $auth = $opauth["auth"];
        $this->type = $auth["info"]["type"];
        $this->token = $auth["credentials"]["token"];
        $this->refresh_token = $auth["credentials"]["refresh_token"];
        $this->client_id = $auth["credentials"]["client_id"];
        $this->client_secret = $auth["credentials"]["client_secret"];

        $this->debug_mode = (bool) $is_debug;

        if(empty($this->type) || empty($this->token)) {
            throw new AuthNotValidException;
        }

        $this->api = new Endpoint($this);
    }

    public function tokenInfo()
    {
        $url = \MOHOStrategy::BASE_URL;
        if($this->debug_mode) {
            $url = \MOHOStrategy::DEBUG_URL;
        }

        return $this->get($url . '/oauth/token/info');
    }

    public function refresh() {
        $url = \MOHOStrategy::BASE_URL;
        if($this->debug_mode) {
            $url = \MOHOStrategy::DEBUG_URL;
        }
        $url .= "/oauth/token";

        $result = $this->post($url, array('refresh_token' => $this->refresh_token,
                                          'grant_type' => 'refresh_token',
                                          'client_id' => $this->client_id,
                                          'client_secret' => $this->client_secret));
        $this->token = $result->access_token;
        $this->refresh_token = $result->refresh_token;

        return $result;
    }

    public function get($url, $data = array(), $options = null, &$responseHeaders = null)
    {
        $data['access_token'] = $this->token;
        return json_decode(self::httpRequest($url . '?' . http_build_query($data, '', '&'), $options, $responseHeaders, $this->debug_mode));
    }

    public function post($url, $data, $options = array(), &$responseHeaders = null)
    {
        if(!is_array($options)) {
            $options = array();
        }

        if(!isset($data['refresh_token'])) {
            $data['access_token'] = $this->token;
        }
        $query = http_build_query($data, '', '&');

        $stream = array('http' => array(
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $query
        ));

        $stream = array_replace_recursive($stream, $options);
        return json_decode(self::httpRequest($url, $stream, $responseHeaders, $this->debug_mode));
    }

    public function put($url, $data, $options = array(), &$responseHeaders = null)
    {
        if(!is_array($options)) {
            $options = array();
        }

        $data['access_token'] = $this->token;
        $query = http_build_query($data, '', '&');

        $stream = array('http' => array(
            'method' => 'PUT',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $query
        ));

        $stream = array_replace_recursive($stream, $options);
        return json_decode(self::httpRequest($url, $stream, $responseHeaders, $this->debug_mode));
    }

    public function delete($url, $data = array(), $options = array(), &$responseHeaders = null)
    {
        if(!is_array($options)) {
            $options = array();
        }

        $data['access_token'] = $this->token;
        $query = http_build_query($data, '', '&');

        $stream = array('http' => array(
            'method' => 'DELETE',
        ));

        $stream = array_replace_recursive($stream, $options);
        return json_decode(self::httpRequest($url . '?' . $query, $stream, $responseHeaders, $this->debug_mode));
    }

    /**
     * Below method from Opauth, for create simple http request
     */
    public static function httpRequest($url, $options, &$responseHeaders = null, $is_debug = false)
    {
        $context = null;
        if(!empty($options) && is_array($options)) {
            if(empty($options['http']['header'])) {
                $options['http']['header'] = "User-Agent: MOHO-API";
            } else {
                $options['http']['header'] .= "\r\nUser-Agent: MOHO-API";
            }
        } else {
            $options = array('http' => array('header' => 'User-Agent: MOHO-API'));
        }
        $context = stream_context_create($options);

        $base_url = Client::API_BASE_URL;
        if($is_debug) {
            $base_url = Client::API_DEBUG_URL;
        }

        ob_start();
        if(strpos($url, "http://") === 0 || strpos($url, "https://") === 0) {
            $content = file_get_contents($url, false, $context);
        } else {
            $content = file_get_contents($base_url . $url, false, $context);
        }

        $warning = ob_get_contents();
        ob_end_clean();
        $responseHeaders = self::httpParseHerader($http_response_header);

        if($warning) {
            throw new HttpRequestException($responseHeaders[1], $responseHeaders[0]);
        }

        return $content;
    }

    public static function httpParseHerader(array $originHeaders, $follow_redirect = true)
    {
        $headers = array();
        $key = '';
        $tempCode = null;
        $statusCode = null;
        $tempReason = null;
        $statusReason = null;

        foreach($originHeaders as $header) {
            $header = explode(":", $header, 2);
            if($tempCode && ($tempCode != 301 && $tempCode != 302) && !$follow_redirect) { // Dont follow redirect, just return current header
                continue;
            }
            if(isset($header[1])) { // HTTP Header Part
                if($tempCode && ($tempCode == 301 || $tempCode == 302) && $follow_redirect) { // Skip parse redirect header until target page
                    continue;
                }

                if(!isset($headers[$header[0]])) {
                    $headers[$header[0]] = trim($header[1]);
                } elseif(is_array($headers[$header[0]])) {
                    $headers[$header[0]] = array_merge($headers[$header[0]], array(trim($header[1])));
                } else {
                    $headers[$header[0]] = array_merge(array($headers[$header[0]]), array(trim($header[1])));
                }

            } else { // Status Code
                $status = explode(' ', $header[0], 3);
                $tempCode = $status[1];
                $tempReason = $status[2];
                if($follow_redirect) {
                    $statusCode = $tempCode;
                    $statusReason = $tempReason;
                }
            }
        }

        return array($statusCode, $statusReason, $headers);
    }

    public function __call($method, $arguments = array())
    {
        if(method_exists($this->api, $method)) {
            return call_user_func_array(array($this->api, $method), $arguments);
        }
        return null;
    }
}

/**
 * Help Exception Class
 */

class AuthNotValidException extends \Exception {}
class HttpRequestException extends \Exception {}
