<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use Yii;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\httpclient\Client;

/**
 * 网关基类
 * @package xutl\payment
 */
class Gateway extends Component implements GatewayInterface
{
    /**
     * @var string gateway service id.
     * This value mainly used as HTTP request parameter.
     */
    private $_id;

    /**
     * @var string gateway service name.
     * This value may be used in database records, CSS files and so on.
     */
    private $_name;

    /**
     * @var bool 是否开启Debug
     */
    public $debug = false;

    /**
     * @var Client
     */
    public $_httpClient;

    /**
     * @param string $id service id.
     */
    public function setId($id)
    {
        $this->_id = $id;
    }

    /**
     * @return string service id
     */
    public function getId()
    {
        if (empty($this->_id)) {
            $this->_id = $this->getName();
        }
        return $this->_id;
    }

    /**
     * @param string $name service name.
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * @return string service name.
     */
    public function getName()
    {
        if ($this->_name === null) {
            $this->_name = $this->defaultName();
        }
        return $this->_name;
    }

    /**
     * Generates service name.
     * @return string service name.
     */
    protected function defaultName()
    {
        return Inflector::camel2id(StringHelper::basename(get_class($this)));
    }

    /**
     * 获取Http Client
     * @return Client
     */
    public function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = new Client();
        }
        return $this->_httpClient;
    }

    public function payment()
    {

    }

    public function refund($outTradeNo)
    {

    }

    public function close($outTradeNo)
    {

    }

    public function find($outTradeNo)
    {

    }

    /**
     * Sets persistent state.
     * @param string $key state key.
     * @param mixed $value state value
     * @return $this the object itself
     */
    protected function setState($key, $value)
    {
        if (!Yii::$app->has('session')) {
            return $this;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $session->set($key, $value);
        return $this;
    }

    /**
     * Returns persistent state value.
     * @param string $key state key.
     * @return mixed state value.
     */
    protected function getState($key)
    {
        if (!Yii::$app->has('session')) {
            return null;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $value = $session->get($key);
        return $value;
    }

    /**
     * Removes persistent state value.
     * @param string $key state key.
     * @return boolean success.
     */
    protected function removeState($key)
    {
        if (!Yii::$app->has('session')) {
            return true;
        }
        /* @var \yii\web\Session $session */
        $session = Yii::$app->get('session');
        $key = $this->getStateKeyPrefix() . $key;
        $session->remove($key);
        return true;
    }

    /**
     * Returns session key prefix, which is used to store internal states.
     * @return string session key prefix.
     */
    protected function getStateKeyPrefix()
    {
        return get_class($this) . '_';
    }

    /**
     * 生成一个指定长度的随机字符串
     * @param int $length
     */
    protected function generateRandomString($length = 32)
    {
        Yii::$app->security->generateRandomString($length);
    }
}