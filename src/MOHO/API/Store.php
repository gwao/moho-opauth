<?php
/**
 * Class Store
 * @author Zheng Xian Qiu
 */
namespace MOHO\API;

class Store
{
    private $client = null;
    public function __construct($client)
    {
        $this->client = $client;
    }

    public function userInfo($id = null)
    {
        if($id && is_numeric($id)) {
            return $this->client->get('/user/' . $id);
        }
        return $this->client->get('/user/info');
    }
}

