<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */
namespace xutl\payment\components;

use xutl\payment\Payment;
use xutl\payment\Trade;
use Yii;
use yii\base\InvalidConfigException;
use xutl\payment\BaseClient;
use yii\web\Request;

/**
 * Class UnionPay
 * @package xutl\payment\components
 */
class UnionPay extends BaseClient
{

    /**
     * 去支付
     * @param Payment $payment 支付模型对象
     * @param array $paymentParams 支付参数
     * @return void
     */
    function payment(Trade $payment, &$paymentParams)
    {
        // TODO: Implement payment() method.
    }

    /**
     * 支付响应
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    public function callback(Request $request, &$paymentId, &$money, &$message, &$payId)
    {
        // TODO: Implement callback() method.
    }

    /**
     * 服务端通知
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    public function notice(Request $request, &$paymentId, &$money, &$message, &$payId)
    {
        // TODO: Implement notice() method.
    }

    /**
     * 统一下单
     * @param array $params
     * @param array $paymentParams
     * @return mixed
     */
    public function preCreate(array $params, array $paymentParams)
    {
        // TODO: Implement preCreate() method.
    }

    /**
     * 查询订单
     * 该接口提供所有微信支付订单的查询，商户可以通过查询订单接口主动查询订单状态，完成下一步的业务逻辑。
     * @param string $outTradeNo
     * @return array
     */
    public function query($outTradeNo)
    {
        // TODO: Implement query() method.
    }
}