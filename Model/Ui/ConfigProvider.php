<?php
/*
 * TLSoft
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the TLSoft license that is
 * available through the world-wide-web at this URL:
 * https://tlsoft.hu/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    TLSoft
 * @package     TLSoft_BarionGateway
 * @copyright   Copyright (c) TLSoft (https://tlsoft.hu/)
 * @license     https://tlsoft.hu/license
 */

namespace TLSoft\CibGateway\Model\Ui;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'cibgateway';

	/**
	 * @var ScopeConfigInterface
	 */
    protected $scopeConfig;

    /**
	 * @param ScopeConfigInterface $scopeConfig
	 */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

	/**
	 * @return array
	 */
    public function getConfig()
    {
        $providers = $this->getProviders($this->scopeConfig->getValue('payment'));
        $this->unifyProviderConfig($providers);

        return [
            'payment' => [
                self::CODE => [
                    'providers' => $providers,
                ]
            ]
        ];
    }

    /**
	 * @param $code
	 * @return array
	 */
    public function getProviderConfig($code)
    {
        $config = $this->getConfig();

        foreach ($config['payment'][self::CODE]['providers'] as $providerConfig) {
            if ($providerConfig['name'] === $code) {
                //return array_merge($this->getCommonConfig(), $providerConfig);
				return $providerConfig;
            }
        }
        return [];
    }

	/**
	 * @param array $scopeConfig
	 * @return array
	 */
    protected function getProviders(array $scopeConfig)
    {
        $prefix = self::CODE;

        $params = array_filter(
            $scopeConfig,
            function (array $data, $key) use ($prefix) {
                return (strpos($key, $prefix) === 0 && (int)$data['active']);
            },
            ARRAY_FILTER_USE_BOTH
        );

        $providers = array_map(
            function ($key, array $data) {
                $data['name'] = $key;
                return $data;
            }, array_keys($params), $params);

        return $providers;
    }

	/**
	 * @param array $providers
	 */
    protected function unifyProviderConfig(array &$providers)
    {
        $keys = $this->collectProviderConfigKeys($providers);

        array_walk($providers, function (array &$provider) use ($keys) {
            $provider = array_merge($keys, $provider);
        });
    }

	/**
	 * @param array $providers
	 * @return array
	 */
    protected function collectProviderConfigKeys(array $providers)
    {
        $keys = [];

        array_walk($providers, function (array $provider) use (&$keys) {
            foreach (array_keys($provider) as $key) {
                if (array_key_exists($key, $keys)) {
                    continue;
                }
                $keys[$key] = null;
            }
        });
        return $keys;
    }

}