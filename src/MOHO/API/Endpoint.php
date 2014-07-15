<?php
/**
 * Class Endpoint
 * @author Zheng Xian Qiu
 */
namespace MOHO\API;

class Endpoint
{
    private $client = null;
    public function __construct($client)
    {
        $this->client = $client;
    }

    // User

    public function userInfo($id = null)
    {
        if($id && is_numeric($id)) {
            return $this->client->get('/user/' . $id);
        }
        return $this->client->get('/user/info');
    }

    // Wallet

    public function wallet($currency = null)
    {
        if($currency) {
            return $this->client->get('/wallet/' . $currency);
        }
        return $this->client->get('/wallet');
    }

    // Bonus Logs

    public function bonusLogs(array $options = array())
    {
        return $this->client->get('/bonus_logs', $options);
    }

    // Order
    public function orders(array $options = array())
    {
        return $this->client->get('/orders', $options);
    }

    public function order($id)
    {
        return $this->client->get('/orders/' . $id);
    }

    public function newOrder(array $datas = array())
    {
        return $this->client->post('/orders', $datas);
    }

    public function updateOrder($id, array $datas = array())
    {
        return $this->client->put('/orders/' . $id, $datas);
    }

    public function deleteOrder($id)
    {
        return $this->client->delete('/orders/' . $id);
    }

    public function payOrder($id)
    {
        return $this->client->get('/orders/' . $id . '/pay');
    }

    // Computing Units
    // NOTE: Update, Create still have bug, didn't provide in this version
    public function computingUnits(array $options = array())
    {
        return $this->client->get('/computing_units', $options);
    }

    // Computing Logics
    public function computingLogics($id, array $options = array())
    {
        return $this->client->get('/computing_units/' . $id, $options);
    }
}

