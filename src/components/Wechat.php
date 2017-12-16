<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\components;

use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\BaseClient;
use yii\httpclient\Client;
use yii\httpclient\RequestEvent;

/**
 * Class Wechat
 * @package xutl\payment\components
 */
class Wechat extends BaseClient
{
    public $baseUrl = 'https://api.mch.weixin.qq.com';

    /**
     * @var string 绑定支付的APPID
     */
    public $appId;

    /**
     * @var string 商户支付密钥
     */
    public $appKey;

    /**
     * @var string 商户号
     */
    public $mchId;

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
        if (empty ($this->appId)) {
            throw new InvalidConfigException ('The "appId" property must be set.');
        }
        if (empty ($this->appKey)) {
            throw new InvalidConfigException ('The "appKey" property must be set.');
        }
        if (empty ($this->mchId)) {
            throw new InvalidConfigException ('The "mchId" property must be set.');
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
        $this->requestConfig['format'] = Client::FORMAT_XML;
        $this->responseConfig['format'] = Client::FORMAT_XML;
        $this->on(Client::EVENT_BEFORE_SEND, [$this, 'RequestEvent']);
    }

    /**
     * 统一下单
     * @param array $params
     * @return mixed
     */
    public function unifiedOrder($params)
    {
        $params = $this->buildPaymentParameter([
            'body' => !empty($params['payId']) ? $params['payId'] : '充值',
            'out_trade_no' => $params['outTradeNo'],
            'total_fee' => round($params['totalFee'] * 100),
            'fee_type' => $params['currency'],
            'spbill_create_ip' => $params['userIp'],
            'trade_type' => $params['tradeType'],
        ]);
        $params['sign'] = $this->createSignature($params);
        /** @var \yii\httpclient\Response $response */
        $response = $this->createRequest()
            ->setUrl('pay/unifiedorder')
            ->setMethod('POST')
            ->setData($params)
            ->send();//统一下单
        return $response->data;

    }

    /**
     * 关闭订单
     * @param string $paymentId
     * @return bool
     * @throws \yii\base\Exception
     */
    public function closeOrder($paymentId)
    {
        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'out_trade_no' => $paymentId,
            'nonce_str' => $this->generateRandomString(),
        ];
        $params['sign'] = $this->createSignature($params);
        $response = $this->post('pay/closeorder', $params);
        if ($response->data['trade_state'] == 'SUCCESS') {
            return true;
        }
        return false;
    }

    /**
     * 签名
     * @param array $parameters
     * @return string
     */
    protected function createSignature(array $parameters)
    {
        foreach ($parameters as $key => $value) {
            if (null == $value || 'null' == $value || 'sign' == $key) {
                unset($parameters[$key]);
            }
        }
        reset($parameters);
        ksort($parameters);
        $bizString = http_build_query($parameters);
        $bizString .= '&sign_type=MD5&key=' . $this->appKey;
        return strtoupper(md5(urldecode(strtolower($bizString))));
    }

    /**
     * 编译支付参数
     * @param array $params
     * @return mixed
     */
    public function buildPaymentParameter($params = [])
    {
        $defaultParams = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => $this->generateRandomString(8),
            'notify_url' => 'http://www.openedu.tv',//$this->getNoticeUrl(),
            'device_info' => 'WEB'
            //'device_info' => isset($this->deviceInfoMap[$params['trade_type']]) ? $this->deviceInfoMap[$params['trade_type']] : 'WEB',
        ];
        return array_merge($defaultParams, $params);
    }

    /**
     * 请求事件
     * @param RequestEvent $event
     * @return void
     */
    public function RequestEvent(RequestEvent $event)
    {

    }
}