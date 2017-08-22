<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\gateways\alipay;

use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\GatewayInterface;

/**
 * Class Alipay
 * @package xutl\payment\gateways\alipay
 */
class Alipay implements GatewayInterface
{
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

        $privateKey = Yii::getAlias($this->privateKey);

        $this->privateKey = openssl_pkey_get_private("file://" . $privateKey);
        if ($this->privateKey === false) {
            throw new InvalidConfigException(openssl_error_string());
        }
        $publicKey = Yii::getAlias($this->publicKey);
        $this->publicKey = openssl_pkey_get_public("file://" . $publicKey);
        if ($this->publicKey === false) {
            throw new InvalidConfigException(openssl_error_string());
        }
    }

    /**
     * 对外接口 - 验证.
     *
     * @param array $data 待签名数组
     * @param string $sign 签名字符串-支付宝服务器发送过来的原始串
     * @param bool $sync 是否同步验证
     *
     * @return  array|boolean
     */
    public function verify($data, $sign = null, $sync = false)
    {
        $sign = is_null($sign) ? $data['sign'] : $sign;
        $toVerify = $sync ? json_encode($data, JSON_UNESCAPED_UNICODE) : $this->getSignContent($data, true);
        return openssl_verify($toVerify, base64_decode($sign), $this->publicKey, OPENSSL_ALGO_SHA256) === 1 ? $data : false;
    }

    /**
     * 签名
     * @return string
     */
    protected function getSign()
    {
        $sign = '';
        if (!openssl_sign($this->getSignContent($this->config), $sign, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception(openssl_error_string());
        }
        return base64_encode($sign);
    }

    /**
     * 获取签名内容
     * @param array $para
     * @param bool $verify
     * @return bool|string
     */
    protected function getSignContent(array $para, $verify = false)
    {
        ksort($para);
        $paraFilter = "";
        foreach ($para as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $paraFilter .= $k . "=" . $v . "&";
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && "@" != substr($v, 0, 1)) {
                $paraFilter .= $k . "=" . $v . "&";
            }
        }
        $paraFilter = substr($paraFilter, 0, -1);
        unset ($k, $v);
        return $paraFilter;
    }
}