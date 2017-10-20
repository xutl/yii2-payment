<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\clients;

use xutl\payment\Request;
use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\BaseClient;
use yii\httpclient\Client;

/**
 * Class Wechat
 * @package xutl\payment
 */
class Wechat extends BaseClient
{
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
     * @var string 网关地址
     */
    public $baseUrl = 'https://api.mch.weixin.qq.com';

    /**
     * @var Client
     */
    private $_httpClient = [
        'class' => 'yii\httpclient\Client',
        'requestConfig' => [
            'format' => Client::FORMAT_XML
        ],
        'responseConfig' => [
            'format' => Client::FORMAT_XML
        ],
    ];

    /**
     * 初始化
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
            'notify_url' => $this->getNoticeUrl(),
            'device_info' => isset($this->deviceInfoMap[$params['trade_type']]) ? $this->deviceInfoMap[$params['trade_type']] : 'WEB',
        ];
        return array_merge($defaultParams, $params);
    }

    /**
     * 统一下单
     * @param Request $request
     */
    public function unifiedOrder(Request $request)
    {
        if ($request->validate()) {
            $params = $this->buildPaymentParameter([
                'body' => !empty($request->payId) ? $request->payId : '充值',
                'out_trade_no' => $request->outTradeNo,
                'total_fee' => round($request->totalFee * 100),
                'fee_type' => $request->currency,
                'spbill_create_ip' => $request->userIp,
                'trade_type' => $request->tradeType,
            ]);
            $params['sign'] = $this->createSignature($params);
            /** @var \yii\httpclient\Response $response */
            $response = $this->createRequest()->setUrl('pay/unifiedorder')->setMethod('POST')->setData($params)->send();//统一下单
            return $response;
        }
    }

    /**
     * 关闭订单
     * @param string $paymentId
     * @return bool
     */
    public function closeOrder($paymentId)
    {
        $params = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'out_trade_no' => $paymentId,
            'nonce_str' => bin2hex(openssl_random_pseudo_bytes(8)),
        ];
        $params['sign'] = $this->createSignature($params);
        $response = $this->api('https://api.mch.weixin.qq.com/pay/closeorder', 'POST', $params);
        if ($response->data['trade_state'] == 'SUCCESS') {
            return true;
        }
        return false;
    }

    /**
     * 发送Http请求
     * @param string $url 请求Url
     * @param string $method 请求方法
     * @param array|string|mixed $params
     * @param array $headers 头
     * @return string
     */
    public function sendRequest($url, $method = 'GET', $params, array $headers = [])
    {
        $request = $this->createRequest()
            ->setMethod($method)
            ->addHeaders($headers)
            ->setUrl($url);
        if (is_array($params)) {
            $request->setData($params);
        } else {
            $request->setContent($params);
        }
        $response = $request->send();
        return $response->content;
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
        $bizString .= '&key=' . $this->appKey;
        return strtoupper(md5(urldecode(strtolower($bizString))));
    }

    /**
     * 转换XML到数组
     * @param \SimpleXMLElement|string $xml
     * @return array
     */
    protected function convertXmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}