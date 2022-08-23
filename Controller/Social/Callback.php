<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_SocialLogin
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\SocialLogin\Controller\Social;

use Hybridauth\Exception\Exception;
use Hybridauth\Hybridauth;
use Hybridauth\HttpClient;
use Hybridauth\Storage\Session;

/**
 * Class Callback
 *
 * @package Mageplaza\SocialLogin\Controller\Social
 */
class Callback extends AbstractSocial
{

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $param = $this->getRequest()->getParams();

        if (isset($param['live.php'])) {
            $request = array_merge($param, ['hauth_done' => 'Live']);
        }
        if ($this->checkRequest('hauth_start', false)
            && (($this->checkRequest('error_reason', 'user_denied')
                    && $this->checkRequest('error', 'access_denied')
                    && $this->checkRequest('error_code', '200')
                    && $this->checkRequest('hauth_done', 'Facebook'))
                || ($this->checkRequest('hauth_done', 'Twitter') && $this->checkRequest('denied')))
        ) {
            return $this->_appendJs(sprintf('<script>window.close();</script>'));
        }

        if (isset($param) && isset($param['hauth_done'])) {
            return $this->processAuth();
        }
        return;
    }

    protected function processAuth()
    {
        $provider = $this->getRequest()->getParam('hauth_done');
        $type = $this->apiHelper->setType(strtolower($provider));

        $config = [
            'base_url'   => $this->apiHelper->getBaseAuthUrl(null),
            'providers'  => [
                $provider => $this->getProviderData($provider)
            ],
            'debug_mode' => false,
            'debug_file' => BP . '/var/log/social.log'
        ];

        try {

            $hybridauth = new Hybridauth($config);
            if ($provider) {
                $parameters = '?' . http_build_query($this->getRequest()->getParams());
                echo "
            <script>
                // Success
                location.href = '/sociallogin/social/login/type/facebook/' + '$parameters';
            </script>";
                exit;
            } else {
                // TODO: Error
            }

        } catch (Exception $e) {
            error_log($e->getMessage());
            echo $e->getMessage();
        }
        return;
    }

    /**
     * @param $key
     * @param null $value
     *
     * @return bool|mixed
     */
    public function checkRequest($key, $value = null)
    {
        $param = $this->getRequest()->getParam($key, false);

        if ($value) {
            return $param === $value;
        }

        return $param;
    }

    /**
     * @param $apiName
     *
     * @return array
     */
    public function getProviderData($apiName)
    {
        $data = [
            'enabled' => $this->apiHelper->isEnabled(),
            'keys'    => [
                'id'     => $this->apiHelper->getAppId(),
                'key'    => $this->apiHelper->getAppId(),
                'secret' => $this->apiHelper->getAppSecret()
            ]
        ];

        return array_merge($data, $this->apiHelper->getSocialConfig($apiName));
    }
}
