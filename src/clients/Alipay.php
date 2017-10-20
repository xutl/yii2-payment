<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\clients;

use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\BaseClient;

/**
 * Class Alipay
 * @package xutl\payment\clients
 */
class Alipay extends BaseClient
{
    /**
     * @var integer
     */
    public $appId;

    /**
     * @var string 私钥
     */
    public $privateKey;

    /**
     * @var string 公钥
     */
    public $publicKey;

    /**
     * @var string 网关地址
     */
    public $baseUrl = 'https://openapi.alipay.com/gateway.do';

    /**
     * 初始化
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        if (!extension_loaded('openssl')) {
            trigger_error('need openssl extension', E_USER_ERROR);
        }
        if (!in_array('sha256', openssl_get_md_methods(), true)) {
            trigger_error('need openssl support sha256', E_USER_ERROR);
        }
        if (empty ($this->appId)) {
            throw new InvalidConfigException ('The "appId" property must be set.');
        }
        if (empty ($this->privateKey)) {
            throw new InvalidConfigException ('The "privateKey" property must be set.');
        }
        if (empty ($this->publicKey)) {
            throw new InvalidConfigException ('The "publicKey" property must be set.');
        }
        $privateKey = "file://" . Yii::getAlias($this->privateKey);
        $this->privateKey = openssl_pkey_get_private($privateKey);
        if ($this->privateKey === false) {
            throw new InvalidConfigException(openssl_error_string());
        }
        $publicKey = "file://" . Yii::getAlias($this->publicKey);
        $this->publicKey = openssl_pkey_get_public($publicKey);
        if ($this->publicKey === false) {
            throw new InvalidConfigException(openssl_error_string());
        }
    }

    /**
     * 统一下单
     * @param array $params
     */
    public function unifiedOrder($params)
    {

    }

    /**
     * 签名
     * @param array $parameters
     * @return string
     */
    protected function signature(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (null == $value || 'null' == $value || 'sign' == $key) {
                unset($parameters[$key]);
            }
        }
        reset($parameters);
        ksort($parameters);
        $bizString = http_build_query($parameters);
        $bizString .= '&key=' . $this->appKey;
        return strtoupper(md5(urldecode(strtolower($bizString))));
    }
}