<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use Yii;
use yii\di\Instance;
use yii\base\Component;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\httpclient\Client;

/**
 * Class BaseClient
 * @package xutl\payment
 */
class BaseClient extends Component implements ClientInterface
{
    /**
     * @var string client service id.
     * This value mainly used as HTTP request parameter.
     */
    private $_id;

    /**
     * @var string client service name.
     * This value may be used in database records, CSS files and so on.
     */
    private $_name;

    /**
     * @var string client service title to display in views.
     */
    private $_title;

    /**
     * @var Client
     */
    public $_httpClient = 'yii\httpclient\Client';

    /**
     * @var array cURL request options. Option values from this field will overwrite corresponding
     * values from [[defaultRequestOptions()]].
     */
    private $_requestOptions = [];

    /**
     * @var StateStorageInterface|array|string state storage to be used.
     */
    private $_stateStorage = 'xutl\payment\SessionStateStorage';

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
     * @param string $title service title.
     */
    public function setTitle($title)
    {
        $this->_title = $title;
    }

    /**
     * @return string service title.
     */
    public function getTitle()
    {
        if ($this->_title === null) {
            $this->_title = $this->defaultTitle();
        }

        return $this->_title;
    }

    /**
     * @return StateStorageInterface stage storage.
     */
    public function getStateStorage()
    {
        if (!is_object($this->_stateStorage)) {
            $this->_stateStorage = Yii::createObject($this->_stateStorage);
        }
        return $this->_stateStorage;
    }

    /**
     * @param StateStorageInterface|array|string $stateStorage stage storage to be used.
     */
    public function setStateStorage($stateStorage)
    {
        $this->_stateStorage = $stateStorage;
    }

    /**
     * 获取Http Client
     * @return Client
     */
    public function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = $this->createHttpClient($this->_httpClient);
        }
        return $this->_httpClient;
    }

    /**
     * Sets HTTP client to be used.
     * @param array|Client $httpClient internal HTTP client.
     */
    public function setHttpClient($httpClient)
    {
        $this->_httpClient = $httpClient;
    }

    /**
     * @param array $options HTTP request options.
     */
    public function setRequestOptions(array $options)
    {
        $this->_requestOptions = $options;
    }

    /**
     * @return array HTTP request options.
     */
    public function getRequestOptions()
    {
        return $this->_requestOptions;
    }

    /**
     * Creates HTTP request instance.
     * @return \yii\httpclient\Request HTTP request instance.
     */
    public function createRequest()
    {
        return $this->getHttpClient()
            ->createRequest()
            ->addOptions($this->defaultRequestOptions())
            ->addOptions($this->getRequestOptions());
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
     * Generates service title.
     * @return string service title.
     */
    protected function defaultTitle()
    {
        return StringHelper::basename(get_class($this));
    }

    /**
     * Creates HTTP client instance from reference or configuration.
     * @param string|array $reference component name or array configuration.
     * @return Client|object HTTP client instance.
     */
    protected function createHttpClient($reference)
    {
        return Instance::ensure($reference, Client::className());
    }

    /**
     * Returns default HTTP request options.
     * @return array HTTP request options.
     * @since 2.1
     */
    protected function defaultRequestOptions()
    {
        return [
            'timeout' => 30,
            'sslVerifyPeer' => false,
        ];
    }

    /**
     * 生成一个指定长度的随机字符串
     * @param int $length
     */
    protected function generateRandomString($length = 32)
    {
        Yii::$app->security->generateRandomString($length);
    }

    /**
     * Sets persistent state.
     * @param string $key state key.
     * @param mixed $value state value
     * @return $this the object itself
     */
    protected function setState($key, $value)
    {
        $this->getStateStorage()->set($this->getStateKeyPrefix() . $key, $value);
        return $this;
    }

    /**
     * Returns persistent state value.
     * @param string $key state key.
     * @return mixed state value.
     */
    protected function getState($key)
    {
        return $this->getStateStorage()->get($this->getStateKeyPrefix() . $key);
    }

    /**
     * Removes persistent state value.
     * @param string $key state key.
     * @return bool success.
     */
    protected function removeState($key)
    {
        return $this->getStateStorage()->remove($this->getStateKeyPrefix() . $key);
    }

    /**
     * Returns session key prefix, which is used to store internal states.
     * @return string session key prefix.
     */
    protected function getStateKeyPrefix()
    {
        return get_class($this) . '_' . $this->getId() . '_';
    }
}