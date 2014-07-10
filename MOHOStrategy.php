<?php
/**
 * MOHO Cloud Platform stragety for Opauth
 *
 * @author Aotoki
 * @link   http://magic-bonus.com/
 */

class MOHOStrategy extends OpauthStrategy
{

    const DEBUG_URL = "http://sandbox.moho.com.tw";
    const BASE_URL = "http://magic-bonus.com";

    /**
     * Compulsory config keys, listed as unassociative arrays
     */
    public $expects = array("client_id", "client_secret");

    /**
     * Optional config keys, without predefining any default values.
     */
    public $optionals = array(
        "redirect_uri", "response_type",
        "sandbox", "debug" // Debug Config
    );

    /**
     * Optional config keys with respective default values, listed as associative arrays
     * eg. array("scope" => "email");
     */
    public $defaults = array(
        "redirect_uri" => "{complete_url_to_strategy}oauth2callaback",
        "response_type" => "code",
        "debug" => false
    );

    public $access_token = null;

    /**
     * Auth request
     */
    public function request()
    {
        $url = $this->getBaseUrl() . "/oauth/authorize";

        $params = array(
            "client_id" => $this->strategy["client_id"],
            "redirect_uri" => $this->strategy["redirect_uri"],
            "response_type" => $this->strategy["response_type"]
        );

        $this->clientGet($url, $params);
    }

    /**
     * Internal Callback
     */

    public function oauth2callaback()
    {
        if(!array_key_exists("code", $_GET) || empty($_GET)) {
            $error = array(
                "provider" => "MOHO",
                "raw" => $_GET
            );
            return $this->errorCallback($error);
        }

        $url = $this->getBaseUrl() . "/oauth/token";

        $params = array(
            "client_id"     => $this->strategy["client_id"],
            "client_secret" => $this->strategy["client_secret"],
            "redirect_uri"  => $this->strategy["redirect_uri"],
            "grant_type"    => "authorization_code",
            "code"          => trim($_GET["code"])
        );

        $response = $this->serverPost($url, $params, null, $heraders);

        $results = json_decode($response);

        if(empty($results) || empty($results->access_token)) {
            $error = array(
                "provider" => "MOHO",
                "raw" => $headers,
                "response" => $response
            );

            return $this->errorCallback($error);
        }

        $userInfo = $this->userInfo($results->access_token);
        $this->auth = array(
            "provider" => "MOHO",
            "uid" => $userInfo->id,
            "info" => array(
                "name" => $userInfo->display_name,
                "email" => $userInfo->email,
                "type" => $userInfo->type
            ),
            "credentials" => array(
                "token" => $results->access_token
            ),
            "raw" => $userInfo
        );

        $this->callback();
    }

    /**
     * User Information
     */

    protected function userInfo($access_token)
    {
        $info = $this->serverGet(
                $this->getBaseUrl() . "/api/v3/user/info",
                array("access_token" => $access_token),
                null,
                $headers);

        if(empty($info)) {
            $error = array(
                "provider" => "MOHO",
                "raw" => $headers
            );
        }

        return json_decode($info);
    }

    /**
     * Get Base URL
     */
    protected function getBaseUrl()
    {
        if($this->strategy["debug"] || $this->strategy["sandbox"]) {
            return MOHOStrategy::DEBUG_URL;
        }

        return MOHOStrategy::BASE_URL;
    }


}

