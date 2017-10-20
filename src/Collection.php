<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use Yii;
use yii\base\Component;
use yii\base\InvalidParamException;

/**
 * Class Collection
 * 'components' => [
 *     'payment' => [
 *         'class' => 'xutl\payment\Collection',
 *         'clients' => [
 *             'Alipay' => [
 *                 'aaa'     => $params['paypal_purse'],
 *                 'vvv'    => $params['paypal_secret'],
 *             ],
 *             'Wechat' => [
 *                 'aaa'   => 'WebMoney',
 *                 'bbb'     => $params['webmoney_purse'],
 *                 'ccc'    => $params['webmoney_secret'],
 *             ],
 *         ],
 *     ],
 * ],
 * @package xutl\payment
 */
class Collection extends Component
{
    /**
     * @var \yii\httpclient\Client|array|string HTTP client instance or configuration for the [[clients]].
     * If set, this value will be passed as 'httpClient' config option while instantiating particular client object.
     * This option is useful for adjusting HTTP client configuration for the entire list of auth clients.
     */
    public $httpClient;

    /**
     * @var array list of Payment clients with their configuration in format: 'clientId' => [...]
     */
    private $_clients = [];

    /**
     * @param array $clients list of payment clients
     */
    public function setClients(array $clients)
    {
        $this->_clients = $clients;
    }

    /**
     * @return ClientInterface[] list of payment clients.
     */
    public function getClients()
    {
        $clients = [];
        foreach ($this->_clients as $id => $client) {
            $clients[$id] = $this->getClient($id);
        }
        return $clients;
    }

    /**
     * @param string $id service id.
     * @return ClientInterface payment client instance.
     * @throws InvalidParamException on non existing client request.
     */
    public function getClient($id)
    {
        if (!array_key_exists($id, $this->_clients)) {
            throw new InvalidParamException("Unknown payment client '{$id}'.");
        }
        if (!is_object($this->_clients[$id])) {
            $this->_clients[$id] = $this->createClient($id, $this->_clients[$id]);
        }
        return $this->_clients[$id];
    }

    /**
     * Checks if client exists in the hub.
     * @param string $id client id.
     * @return bool whether client exist.
     */
    public function hasClient($id)
    {
        return array_key_exists($id, $this->_clients);
    }

    /**
     * Creates payment client instance from its array configuration.
     * @param string $id auth client id.
     * @param array $config payment client instance configuration.
     * @return object|ClientInterface payment client instance.
     */
    protected function createClient($id, $config)
    {
        $config['id'] = $id;
        if (!isset($config['httpClient']) && $this->httpClient !== null) {
            $config['httpClient'] = $this->httpClient;
        }
        return Yii::createObject($config);
    }
}