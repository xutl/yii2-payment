<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use xutl\payment\components\UnionPay;
use yii\di\ServiceLocator;
use yii\base\InvalidConfigException;
use xutl\payment\components\AliPay;
use xutl\payment\components\JdPay;
use xutl\payment\components\Wechat;

/**
 * Class Payment
 * @package xutl\payment
 */
class Payment extends ServiceLocator
{
    /**
     * Payment constructor.
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->preInit($config);
        parent::__construct($config);
    }

    /**
     * 预处理组件
     * @param array $config
     */
    public function preInit(&$config)
    {
        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }

    /**
     * 获取 AliPay 实例
     * @return object|AliPay
     * @throws InvalidConfigException
     */
    public function getAlipay()
    {
        return $this->get('alipay');
    }

    /**
     * 获取 Wechat 实例
     * @return object|Wechat
     * @throws InvalidConfigException
     */
    public function getWechat()
    {
        return $this->get('wechat');
    }

    /**
     * 获取 Jdpay 实例
     * @return object|JdPay
     * @throws InvalidConfigException
     */
    public function getJdpay()
    {
        return $this->get('jdpay');
    }

    /**
     * 获取 unionpay 实例
     * @return object|UnionPay
     * @throws InvalidConfigException
     */
    public function getUnionpay()
    {
        return $this->get('unionpay');
    }

    /**
     * Returns the configuration of payment components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'alipay' => ['class' => 'xutl\payment\components\AliPay'],
            'wechat' => ['class' => 'xutl\payment\components\Wechat'],
            'jdpay' => ['class' => 'xutl\payment\components\JdPay'],
            'unionpay'=>['class' => 'xutl\payment\components\UnionPay'],
        ];
    }
}