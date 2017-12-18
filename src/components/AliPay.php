<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment\components;

use xutl\payment\OrderInterface;
use xutl\payment\PaymentException;
use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use yii\httpclient\Client;
use yii\httpclient\Exception;
use yii\httpclient\RequestEvent;
use xutl\payment\BaseClient;

/**
 * Class Alipay
 * @package xutl\payment\components
 */
class AliPay extends BaseClient
{
    const SIGNATURE_METHOD_RSA = 'RSA';
    const SIGNATURE_METHOD_RSA2 = 'RSA2';

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
     * @var string 签名方法
     */
    public $signType = self::SIGNATURE_METHOD_RSA2;

    /**
     * @var string 网关地址
     */
    public $baseUrl = 'https://openapi.alipay.com';

    /**
     * 初始化
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
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

        $this->responseConfig['format'] = Client::FORMAT_JSON;
        $this->on(Client::EVENT_BEFORE_SEND, [$this, 'RequestEvent']);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Alipay';
    }

    /**
     * 统一下单
     * @param array $params
     * @return array
     * @throws PaymentException
     */
    public function preCreate(array $params)
    {
        $data = [
            'method' => 'alipay.trade.precreate',
            'biz_content' => [
                'out_trade_no' => $params['id'],//商户订单号
                'total_amount' => $params['total_amount'],//订单总金额
                'subject' => $params['subject'],//订单标题
                //'seller_id' => '2088102171430364',//卖家支付宝用户ID

                //'discountable_amount' => '',//可打折金额
                'return_url' => 'http://dev.yuncms.net',
                'notify_url' => 'http://dev.yuncms.net',
            ],
        ];
        return $this->sendRequest($data);
    }

    /**
     * @param array $params
     * @return array|bool
     * @throws PaymentException
     */
    public function create(array $params)
    {
        $data = [
            'method' => 'alipay.trade.create',
            'biz_content' => [
                'out_trade_no' => $order->outTradeNo,
                'total_amount' => $order->totalAmount,
                'subject' => $order->subject
            ],
        ];
        return $this->sendRequest($data);
    }

    /**
     * 查询支付
     * @param string $outTradeNo 交易号
     * @return array|bool
     * @throws PaymentException
     */
    public function query($outTradeNo)
    {
        $data = [
            'method' => 'alipay.trade.query',
            'biz_content' => [
                'out_trade_no' => $outTradeNo,
            ],
        ];
        return $this->sendRequest($data);
    }

    /**
     *
     */
    public function pay()
    {
        $params['method'] = 'alipay.trade.create';
    }

    /**
     * 关闭支付
     * @param string $outTradeNo
     * @return bool|void
     */
    public function close($outTradeNo)
    {

    }

    public function cancel()
    {

    }

    /**
     * 统一收单退款接口
     * @return mixed|void
     */
    public function refund()
    {
        $params['method'] = 'alipay.trade.fastpay.refund.query';
        $params['biz_content'] = '';
    }

    public function refundQuery()
    {

    }

    public function orderSettle()
    {

    }

    /**
     * 网关请求参数
     * @param array $params
     * @return array|bool
     * @throws PaymentException
     */
    public function sendRequest(array $params)
    {
        $response = $this->post('gateway.do', $params)->send();
        if ($response->isOk) {
            $responseNode = str_replace('.', '_', $params['method']) . '_response';
            if (isset($response->data[$responseNode]) && isset($response->data['sign'])) {
                print_r($response->data);
                return $this->verify($response->data[$responseNode], $response->data['sign'], true);
            } else {
                throw new PaymentException('Http request failed.');
            }
        } else {
            throw new PaymentException('Gateway Exception');
        }
    }

    /**
     * 验证支付宝支付宝通知
     * @param array $data 通知数据
     * @param null $sign 数据签名
     * @param bool $sync
     * @return array|bool
     */
    public function verify($data, $sign = null, $sync = false)
    {
        $sign = is_null($sign) ? $data['sign'] : $sign;
        $toVerify = $sync ? json_encode($data) : $this->getSignContent($data, true);
        return openssl_verify($toVerify, base64_decode($sign), $this->publicKey, OPENSSL_ALGO_SHA256) === 1 ? $data : false;
    }

    /**
     * 请求事件
     * @param RequestEvent $event
     * @return void
     */
    public function RequestEvent(RequestEvent $event)
    {
        $params = $event->request->getData();
        $params = ArrayHelper::merge([
            'app_id' => $this->appId,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => $this->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        ], $params);
        $params['biz_content'] = Json::encode($params['biz_content']);
        //签名
        if ($this->signType == self::SIGNATURE_METHOD_RSA2) {
            $params['sign'] = openssl_sign($this->getSignContent($params), $sign, $this->privateKey, OPENSSL_ALGO_SHA256) ? base64_encode($sign) : null;
        } elseif ($this->signType == self::SIGNATURE_METHOD_RSA) {
            $params['sign'] = openssl_sign($this->getSignContent($params), $sign, $this->privateKey, OPENSSL_ALGO_SHA1) ? base64_encode($sign) : null;
        }
        $event->request->setData($params);
    }

    /**
     * 数据签名处理
     * @param array $toBeSigned
     * @param bool $verify
     * @return bool|string
     */
    protected function getSignContent(array $toBeSigned, $verify = false)
    {
        ksort($toBeSigned);
        $stringToBeSigned = '';
        foreach ($toBeSigned as $k => $v) {
            if ($verify && $k != 'sign' && $k != 'sign_type') {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
            if (!$verify && $v !== '' && !is_null($v) && $k != 'sign' && '@' != substr($v, 0, 1)) {
                $stringToBeSigned .= $k . '=' . $v . '&';
            }
        }
        $stringToBeSigned = substr($stringToBeSigned, 0, -1);
        unset($k, $v);
        return $stringToBeSigned;
    }
}