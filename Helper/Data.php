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

namespace TLSoft\BarionGateway\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;

class Data extends AbstractHelper
{

    /**
     * @var Resolver
     */
    private $localeResolver;

    /**
     * @var TimezoneInterface
     */
    private $timezoneFactory;

    /**
     * @var UrlInterface
     */
    private $urlFactory;

    /**
     * @var $directoryList
     */
    private $directoryList;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * __construct
     * @param Context $context
     * @param StoreRepositoryInterface $store
     * @param TimezoneInterface $timezoneFactory
     * @param UrlInterface $urlInterface
     */
    public function __construct(
        Context $context,
        Resolver $localeResolver,
        TimezoneInterface $timezoneFactory,
        UrlInterface $urlInterface,
        DirectoryList $directoryList,
        JsonHelper $jsonHelper
    )
    {
        parent::__construct($context);
        $this->localeResolver = $localeResolver;
        $this->timezoneFactory = $timezoneFactory;
        $this->urlFactory = $urlInterface;
        $this->directoryList = $directoryList;
        $this->jsonHelper = $jsonHelper;
    }

    /**
     * Get allowed currencies
     *
     * @return array
     */
    public function getAllowedCurrencyCodes()
    {
        return explode(",", $this->getConfig("payment/bariongateway/allowedcurrency"));
    }

    /**
     * Summary of getConfig
     * @param string $path
     * @return boolean|string
     */
    protected function getConfig(string $path)
    {
        if ($path) {
            $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE);
            return $value;
        }

        return false;
    }

    /**
     * Transform internal locale code to Barion locale code.
     * @param int $storeId
     * @return string
     */
    public function getLocaleCode(int $storeId)
    {

        $localecode = $this->localeResolver->getLocale();

        $localecode = substr($localecode, 3);
        $enabledlocales = explode(",", $this->getConfig("payment/bariongateway/enabledlocales"));
        $endlocale = "";
        if ($localecode == "US" || $localecode == "GB") {
            $localecode = "EN";
        }
        foreach ($enabledlocales as $enabledlocale){
            if ($enabledlocale == $localecode){
                $endlocale=$localecode;
            }
        }
        if (empty($endlocale)){
            $endlocale = "EN";
        }

        if($endlocale=="EN")
        {
            $endlocale = "en-US";
        }else{
            $endlocale = strtolower($endlocale)."-".$endlocale;
        }

        return $endlocale;
    }

    /**
     * Get current timecode for Barion transactions
     * @return string
     */
    public function getTimeCode()
    {
        return $this->timezoneFactory->date()->format('YmdHis');
    }

    /**
     * Format order total to Barion's requirements.
     * @param number $total
     * @param string $currency
     * @return string
     */
    public function formatOrderTotal($total, $currency)
    {

        if ($currency == 'HUF') {
            $total = number_format($total, 0, '', '');
        } else {
            $total = number_format($total, 2, '.', '');
        }

        return $total;
    }

    /**
     * get url from url path
     * @param string $path
     * @return string
     */
    public function getUrl(string $path)
    {
        return $this->urlFactory->getUrl($path);
    }

    /**
     * Create Barion message
     * @param array $message
     * @return string
     */
    public function convertMessage(array $message)
    {
        $text = $this->getEncodedMessage($message);
        return $text;
    }

    /**
     * Return encoded Barion message
     * @return string
     */
    public function getEncodedMessage(array $message)
    {

        return $this->jsonHelper->jsonEncode($message);

    }

    /**
     * Get Barion state url
     * @return boolean|string
     */
    public function getStateUrl()
    {
        $test_mode = $this->getConfig("payment/bariongateway/test_mode");

        if ($test_mode == 1) {
            $url = $this->getConfig("payment/bariongateway/state_url_test");
        } else {
            $url = $this->getConfig("payment/bariongateway/state_url");
        }

        if (empty($url)) {
            $url = false;
        }
        return $url;
    }

    /**
     * Get Barion url
     * @return boolean|string
     */
    public function getMarketUrl()
    {
        $test_mode = $this->getConfig("payment/bariongateway/test_mode");

        if ($test_mode == 1) {
            $url = $this->getConfig("payment/bariongateway/start_url_test");
        } else {
            $url = $this->getConfig("payment/bariongateway/start_url");
        }

        if (empty($url)) {
            $url = false;
        }
        return $url;
    }

    /**
     * Get Barion redirect url
     * @return boolean|string
     */
    public function getCustomerUrl()
    {
        $test_mode = $this->getConfig("payment/bariongateway/test_mode");

        $url = false;

        if ($test_mode == 1) {
            $url = $this->getConfig("payment/bariongateway/redirect_url_test");
        } else {
            $url = $this->getConfig("payment/bariongateway/redirect_url");
        }

        if (empty($url)) {
            $url = false;
        }
        return $url;
    }

    /**
     * Return decoded Barion message
     * @param string $message
     * @return array
     */
    public function getDecodedMessage(string $message)
    {
        $result = $this->jsonHelper->jsonDecode($message);

        return $result;

    }


}