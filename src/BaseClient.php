<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace xutl\payment;

use Yii;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\helpers\Url;
use yii\httpclient\Client;
use yii\web\Request;

/**
 * Class BaseClient
 * @package xutl\payment
 */
abstract class BaseClient extends Client implements ClientInterface
{
    /**
     * @var string 跳转方法
     */
    public $redirectMethod = 'POST';

    /**
     * @var string Web付款 跳转URL
     */
    public $redirectUrl;

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
     * @var string URL, which user will be redirected after authentication at the Payment provider web site.
     * Note: this should be absolute URL (with http:// or https:// leading).
     * By default current URL will be used.
     */
    private $_returnUrl;

    /**
     * @var string 后端通知地址
     */
    private $_noticeUrl;

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
     * Generates service name.
     * @return string service name.
     */
    protected function defaultName()
    {
        return Inflector::camel2id(StringHelper::basename(get_class($this)), '');
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
     * @param string $returnUrl return URL
     */
    public function setReturnUrl($returnUrl)
    {
        $this->_returnUrl = $returnUrl;
    }

    /**
     * @return string return URL.
     */
    public function getReturnUrl()
    {
        if ($this->_returnUrl === null) {
            $this->_returnUrl = $this->defaultReturnUrl();
        }
        return $this->_returnUrl;
    }

    /**
     * @param string $noticeUrl return URL
     */
    public function setNoticeUrl($noticeUrl)
    {
        $this->_noticeUrl = $noticeUrl;
    }

    /**
     * @return string return URL.
     */
    public function getNoticeUrl()
    {
        if ($this->_noticeUrl === null) {
            $this->_noticeUrl = $this->defaultNoticeUrl();
        }
        return $this->_noticeUrl;
    }

    /**
     * Composes default [[returnUrl]] value.
     * @return string return URL.
     */
    public function defaultReturnUrl()
    {
        return Url::to(['/trade/response/return', 'gateway' => $this->getId()], true);
    }

    /**
     * Composes default [[noticeUrl]] value.
     * @return string return URL.
     */
    public function defaultNoticeUrl()
    {
        return Url::to(['/trade/response/notice', 'gateway' => $this->getId()], true);
    }

    /**
     * 跳转去支付
     * @param array $params 支付参数
     * @throws \Exception
     */
    public function getRedirectResponse($params)
    {
        if ('GET' === $this->redirectMethod) {
            $url = $this->composeUrl($this->redirectUrl, $params);
            Yii::$app->response->redirect($url);
            Yii::$app->end();
        } elseif ('POST' === $this->redirectMethod) {
            $hiddenFields = '';
            foreach ($params as $key => $value) {
                $hiddenFields .= sprintf(
                        '<input type="hidden" name="%1$s" value="%2$s" />',
                        htmlentities($key, ENT_QUOTES, 'UTF-8', false),
                        htmlentities($value, ENT_QUOTES, 'UTF-8', false)
                    ) . "\n";
            }
            $output = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title></head><body onload="document.forms[0].submit();" style=" text-align:center;background-color: #eff0f1;"><img src="data:image/gif;base64,R0lGODlhMgAyAKIHAP////vZu/JyBfvavvvavfJ2DPN4D////yH/C05FVFNDQVBFMi4wAwEAAAAh+QQFBwAHACwAAAAAMgAyAEADpXi63P4wygmFETTLwHvIQCgCWjOKpXOGqcmmaNuupGzfeK5PwfAxgqAQw4jtjjlj8mWjuWoLJ3JKrVqvk6EQe/B0GhZidHXs/UrKJnMJ1UjVJ65c8ZbUcel7Gruf+/+AgYKDO0MFhApaFnIeBA0FQodFIztePSBkOgQdjm6UiKBccWxrFHcwmZ59qKuhrqRtE6OwpRCnMrN0n2OtR7d/ua/Cw8Q7CQAh+QQFBwAHACwMAAgAGwALAEADNHi6rPEtHkArkFHoLVRtTwgxG8aIoTlZFDguXLleaqbVjoib6IuxrhSJM7PsYjfg7hA7JAAAIfkEBQcABwAsFgAIABUAFQBAAzd4B6z+MMZAa5DYic0F+81TZWRpHpwCfqfDihbVKuMcpXOXrvManjxY7RSTtWK2pLKky+kEzlQCACH5BAUHAAcALCAADAALABsAQAM0eAes/jC6QJW4WDzGm6RgdWBRx5WeNIlPCKpORkIzZNomgJ9w7x8uiIulCP58MppM80g+EgAh+QQFBwAHACwWABYAFQAVAEADOHi63AfQyamEvaKFTfuDINBtJNdcXiqFoLq0KjmhTCkvGO2q7M5Grt5uSNSUdjYTDmO8LXM7qCsBACH5BAUHAAcALAwAIAAbAAsAQAMzeCes/tCBGWO4OFTHensYNI2T120iSS2fk4XPiELwzJ62RN5efqgUEy2Tks1eF9/mZUsAACH5BAUHAAcALAgAFgAVABUAQAM4eCes/jDKOYOFIGvAeqOVJQZgiWWK15nPyobke4ziCcjHpi2erH4sFW5ILNFktMvr+NDJdKiXMwEAIfkEBQcABwAsCAAMAAsAGwBAAzR4utL7cIUZH7gYvMbb/FSlYFHHlY4UPuAnvmN2RSR6bqYAmXCvuqxWANLyGSsyiGxmyRwSADs=" style="width:80px;margin-left:auto; margin-top:200px "><form action="%1$s" method="post" style="opacity:0"><p>Redirecting to payment page...</p><p>%2$s<input type="submit" value="Continue" /></p></form></body></html>';
            $output = sprintf(
                $output,
                htmlentities($this->redirectUrl, ENT_QUOTES, 'UTF-8', false),
                $hiddenFields
            );
            Yii::$app->response->content = $output;
            Yii::$app->end();
        }
        throw new \Exception('Invalid redirect method "' . $this->redirectMethod . '".');
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
    abstract public function callback(Request $request, &$paymentId, &$money, &$message, &$payId);

    /**
     * 服务端通知
     * @param Request $request
     * @param $paymentId
     * @param $money
     * @param $message
     * @param $payId
     * @return mixed
     */
    abstract public function notice(Request $request, &$paymentId, &$money, &$message, &$payId);

    /**
     * 生成一个指定长度的随机字符串
     * @param int $length
     * @return string
     * @throws \yii\base\Exception
     */
    protected function generateRandomString($length = 32)
    {
        return Yii::$app->security->generateRandomString($length);
    }

    /**
     * 工具方法 从基本网址和GET参数中生成网址。
     * @param string $url base URL.
     * @param array $params GET params.
     * @return string composed URL.
     */
    protected function composeUrl($url, array $params = [])
    {
        if (strpos($url, '?') === false) {
            $url .= '?';
        } else {
            $url .= '&';
        }
        $url .= http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        return $url;
    }
}