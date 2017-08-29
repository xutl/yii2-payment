<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\gateways;

use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\Gateway;
use yii\httpclient\Client;

/**
 * Class Wechat
 * @package xutl\payment\gateways
 */
class Wechat extends Gateway
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

    private $unifiedOrderUrl = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
    private $closeOrderUrl = 'https://api.mch.weixin.qq.com/pay/closeorder';
    private $orderQueryUrl = 'https://api.mch.weixin.qq.com/pay/orderquery';
    private $refundUrl = 'https://api.mch.weixin.qq.com/secapi/pay/refund';

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
     * @return array
     */
    public function buildPaymentParameter($params = [])
    {
        $defaultParams = [
            'appid' => $this->appId,
            'mch_id' => $this->mchId,
            'nonce_str' => bin2hex(openssl_random_pseudo_bytes(8)),
        ];
        return array_merge($defaultParams, $params);
    }

    /**
     * 统一下单
     * @param array $params
     * @return array
     */
    public function unifiedOrder(array $params)
    {
        $params = $this->buildPaymentParameter($params);
        $params['sign'] = $this->makeSign($params);
        $response = $request = $this->getHttpClient()->post($this->unifiedOrderUrl, $params)->send();
        return $response->data;
    }

    /**
     * 关闭订单
     * @param string $outTradeNo
     * @return bool
     */
    public function close($outTradeNo)
    {
        $params = $this->buildPaymentParameter(['out_trade_no' => $outTradeNo]);
        $params['sign'] = $this->makeSign($params);
        $response = $this->getHttpClient()->post($this->closeOrderUrl, $params)->send();
        return $response->data;
    }

    /**
     * 查询支付单
     * @param string $outTradeNo
     * @return array
     */
    public function find($outTradeNo)
    {
        $params = $this->buildPaymentParameter(['out_trade_no' => $outTradeNo]);
        $params['sign'] = $this->makeSign($params);
        $response = $this->getHttpClient()->post($this->orderQueryUrl, $params)->send();
        return $response->data;
    }

    /**
     * 退款
     * @param string $outTradeNo 商户订单号
     * @param string $outRefundNo 商户退款单号
     * @return mixed
     */
    public function refund($outTradeNo, $outRefundNo)
    {
        $params = $this->buildPaymentParameter([
            'out_trade_no' => $outTradeNo,
            'out_refund_no' => $outRefundNo
        ]);
        $params['sign'] = $this->makeSign($params);
        $response = $this->getHttpClient()->post($this->refundUrl, $params)->send();
        return $response->data;
    }

    /**
     * 创建签名
     * @param array $data
     * @return string
     */
    protected function makeSign($data)
    {
        ksort($data);
        $string = md5($this->getSignContent($data) . "&key=" . $this->appKey);
        return strtoupper($string);
    }

    /**
     * 构建无空值的Http查询字符串
     * @param array $data
     * @return string
     */
    protected static function httpBuildQueryWithoutNull(array $data)
    {
        foreach ($data as $key => $value) {
            if (null == $value || 'null' == $value || 'sign' == $key) {
                unset($data[$key]);
            }
        }
        reset($data);
        ksort($data);
        return http_build_query($data);
    }

    /**
     * 获取待签名的内容
     * @param array $data
     * @return string
     */
    protected function getSignContent(array $data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            $buff .= ($k != "sign" && $v != "" && !is_array($v)) ? $k . "=" . $v . "&" : '';
        }
        return trim($buff, "&");
    }

    /**
     * 转换XML到数组
     * @param \SimpleXMLElement|string $xml
     * @return array
     */
    protected function convertXmlToArray($xml)
    {
        if (!$xml) {
            throw new \InvalidArgumentException("convert to array error !invalid xml");
        }
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }

    /**
     * 转换数组到XML
     * @param array $data
     * @return string
     */
    protected function convertArrayToXml(array $data)
    {
        if (!is_array($data) || count($data) <= 0) {
            throw new \InvalidArgumentException("convert to xml error!invalid array!");
        }
        $xml = "<xml>";
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? "<" . $key . ">" . $val . "</" . $key . ">" :
                "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 获取Http Client
     * @return Client
     */
    public function getHttpClient()
    {
        if (!is_object($this->_httpClient)) {
            $this->_httpClient = new Client([
                'requestConfig' => [
                    'format' => Client::FORMAT_XML
                ],
                'responseConfig' => [
                    'format' => Client::FORMAT_XML
                ],
            ]);
        }
        return $this->_httpClient;
    }
}