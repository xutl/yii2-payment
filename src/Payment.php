<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use yii\di\ServiceLocator;

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
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
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
     * @return object|Alipay
     * @throws InvalidConfigException
     */
    public function getAlipay()
    {
        return $this->get('alipay');
    }

    /**
     * 获取 AliPay 实例
     * @return object|Wechat
     * @throws InvalidConfigException
     */
    public function getWechat()
    {
        return $this->get('wechat');
    }

    /**
     * 获取 AliPay 实例
     * @return object|Jdpay
     * @throws InvalidConfigException
     */
    public function getJdpay()
    {
        return $this->get('jdpay');
    }


    /**
     * Returns the configuration of payment components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'alipay' => ['class' => 'xutl\payment\Alipay'],
            'wechat' => ['class' => 'xutl\payment\Wechat'],
            'jdpay' => ['class' => 'xutl\payment\Jdpay'],

        ];
    }
}