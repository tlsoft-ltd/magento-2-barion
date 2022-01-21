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

namespace TLSoft\CibGateway\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;

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
        DirectoryList $directoryList
    )
    {
        parent::__construct($context);
        $this->localeResolver = $localeResolver;
        $this->timezoneFactory = $timezoneFactory;
        $this->urlFactory = $urlInterface;
        $this->directoryList = $directoryList;
    }

    /**
     * Get allowed currencies
     *
     * @return array
     */
    public function getAllowedCurrencyCodes()
    {
        return explode(",", $this->getConfig("payment/cibgateway/allowedcurrency"));
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
     * Transform internal locale code to CIB locale code.
     * @param int $storeId
     * @return string
     */
    public function getLocaleCode(int $storeId)
    {

        $localecode = $this->localeResolver->getLocale();

        $ciblocale = substr($localecode, 3);
        $enabledlocales = explode(",", $this->getConfig("payment/cibgateway/enabledlocales"));
        $endciblocale = "";
        if ($ciblocale == "US" || $ciblocale == "GB") {
            $ciblocale = "EN";
        }
        foreach ($enabledlocales as $enabledlocale) {
            if ($enabledlocale == $ciblocale) {
                $endciblocale = $ciblocale;
            }
        }
        if (empty($endciblocale)) {
            $endciblocale = "EN";
        }

        return $endciblocale;
    }

    /**
     * Get current timecode for CIB transactions
     * @return string
     */
    public function getTimeCode()
    {
        return $this->timezoneFactory->date()->format('YmdHis');
    }

    /**
     * Format order total to CIB's requirements.
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
     * Create CIB message
     * @param array $message
     * @param int $id
     * @return string
     */
    public function convertMessage(array $message, int $id)
    {
        $text = "";
        $formattedMsg = array();
        switch ($id) {
            case 10:
                $format = $this->msgt10();
                $formattedMsg["AUTH"] = 0;
                break;
            case 20:
                $format = $this->msgt20();
                break;
            case 32:
                $format = $this->msgt32();
                break;
            case 33:
                $format = $this->msgt33();
                break;
            case 78:
                $format = $this->msgt78();
                break;
            case 74:
                $format = $this->msgt78();
                break;
            case 80:
                $format = $this->msgt80();
                break;
            case 70:
                $format = $this->msgt70();
                break;
            default:
                break;
        }

        $formattedMsg = array_merge($formattedMsg, $this->processFormat($format, $message));
        $formattedMsg["MSGT"] = $id;
        $formattedMsg["CRYPTO"] = 1;

        $text = http_build_query($formattedMsg, '', '&');
        $text = $this->getEncodedMessage($text);
        return $text;
    }

    /**
     * MSGT 10 coded message
     * @return array
     */
    protected function msgt10()
    {
        return array(
            "pid" => "pid",
            "uid" => "userid",
            "amo" => "amount",
            "cur" => "currency_code",
            "ts" => "timecode",
            "lang" => "language_code",
            "url" => "return_url",
            "trid" => "transaction_id"
        );
    }

    /**
     * MSGT 10 coded message
     * @return array
     */
    protected function msgt20()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid"
        );
    }

    /**
     * MSGT 32 coded message
     * @return string[]
     */
    protected function msgt32()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid",
            "amo" => "amount"
        );
    }

    /**
     * MSGT 33 coded message
     * @return string[]
     */
    protected function msgt33()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid",
            "amo" => "amount"
        );
    }

    /**
     * MSGT 78 coded message
     * @return string[]
     */
    protected function msgt78()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid",
            "amo" => "amo"
        );
    }

    /**
     * MSGT 80 coded message
     * @return string[]
     */
    protected function msgt80()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid",
            "amoorig" => "amoorig",
            "amonew" => "amonew"
        );
    }

    /**
     * MSGT 80 coded message
     * @return string[]
     */
    protected function msgt70()
    {
        return array(
            "pid" => "pid",
            "trid" => "trid",
            "amo" => "amo"
        );
    }

    /**
     * Format CIB message array
     * @param array $format
     * @param array $message
     * @return array
     */
    protected function processFormat(array $format, array $message)
    {
        $returnMessage = array();
        $message = array_change_key_case($message, CASE_LOWER);
        foreach ($format as $key => $value) {
            if (array_key_exists($value, $message)) {
                $returnMessage[strtoupper($key)] = $message[$value];
            }
        }

        return $returnMessage;
    }

    /**
     * Return encoded CIB message
     * @param string $message
     * @return string
     */
    public function getEncodedMessage(string $message)
    {

        $path = $this->getConfig("payment/cibgateway/keyfile");

        $file = $this->directoryList->getPath("media") . "/keyfile/" . $path;

        $message = $this->encodeMessage($message, $file);

        return $message;

    }

    /**
     * Encode CIB message
     * @param string $message
     * @param string $path
     * @return string
     */
    protected function encodeMessage(string $message, string $path)
    {
        $cleartext = $message;
        $arr = explode("&", $cleartext);
        $ciphertext = "";
        $pid = "";
        // Strip CRYPTO and get PID
        for ($i = 0; $i < count($arr); $i++) {
            if (strtoupper($arr[$i]) != "CRYPTO=1")
                $ciphertext .= "&" . $arr[$i];
            if (substr(strtoupper($arr[$i]), 0, 4) == "PID=")
                $pid = substr(strtoupper($arr[$i]), 4, 7);
        }
        $ciphertext = substr($ciphertext, 1);
        // URL encode
        $ciphertext = rawurlencode($ciphertext);
        $ciphertext = str_replace("%3D", "=", $ciphertext);
        $ciphertext = str_replace("%26", "&", $ciphertext);
        // Calculate and append CRC32
        $crc = str_pad(dechex(crc32($ciphertext)), 8, "0", STR_PAD_LEFT);
        for ($i = 0; $i < 4; $i++)
            $ciphertext .= chr(base_convert(substr($crc, $i * 2, 2), 16, 10));
        // 3DES
        $f = fopen($path, "r");
        $keyinfo = fread($f, 38);
        fclose($f);
        $key1 = substr($keyinfo, 14, 8);
        $key2 = substr($keyinfo, 22, 8);
        $iv = substr($keyinfo, 30, 8);
        $key = $key1 . $key2 . $key1;
        $ciphertext = openssl_encrypt($ciphertext, "DES-EDE3-CBC", $key, OPENSSL_RAW_DATA, $iv);
        // Pad length to mod3
        $pad = 3 - (strlen($ciphertext) % 3);
        for ($i = 0; $i < $pad; $i++)
            $ciphertext .= chr($pad);
        // Base64
        $ciphertext = base64_encode($ciphertext);
        // URL encode
        $ciphertext = rawurlencode($ciphertext);
        $ciphertext = "PID=" . $pid . "&CRYPTO=1&DATA=" . $ciphertext;
        return $ciphertext;
    }

    /**
     * Get CIB Market url
     * @return boolean|string
     */
    public function getMarketUrl()
    {
        $test_mode = $this->getConfig("payment/cibgateway/test_mode");

        $url = false;

        if ($test_mode == 1) {
            $url = $this->getConfig("payment/cibgateway/market_test_url");
        } else {
            $url = $this->getConfig("payment/cibgateway/market_url");
        }

        if (empty($url)) {
            $url = false;
        }
        return $url;
    }

    /**
     * Get CIB Customer redirect url
     * @return boolean|string
     */
    public function getCustomerUrl()
    {
        $test_mode = $this->getConfig("payment/cibgateway/test_mode");

        $url = false;

        if ($test_mode == 1) {
            $url = $this->getConfig("payment/cibgateway/customer_test_url");
        } else {
            $url = $this->getConfig("payment/cibgateway/customer_url");
        }

        if (empty($url)) {
            $url = false;
        }
        return $url;
    }

    /**
     * Return decoded CIB message
     * @param string $message
     * @return array
     */
    public function getDecodedMessage(string $message)
    {
        $result = array();

        if (strpos($message, "RC=") !== FALSE && strpos($message, "CIB E-commerce hiba") === FALSE) {
            $result = array();
            $result["RC"] = str_replace("RC=", "", $message);
            return $result;
        } else if (strpos($message, "CIB E-commerce hiba") !== FALSE) {
            $result['RC'] = false;
            return $result;
        }

        $path = $this->getConfig("payment/cibgateway/keyfile");

        $file = $this->directoryList->getPath("media") . "/keyfile/" . $path;

        if ($message == "RC=S05") {
            return $result;
        }
        $message = $this->decodeMessage($message, $file);

        $result = $this->splitResult($message);

        if (array_key_exists('RT', $result)) {
            $result['RT'] = iconv('ISO8859-2', 'UTF-8', urldecode($result['RT']));
        }

        return $result;

    }

    /**
     * Decode CIB message
     * @param string $message
     * @param string $path
     * @return string
     */
    protected function decodeMessage(string $message, string $path)
    {
        $arr = explode("&", $message);
        if (!is_array($arr))
            return false;
        $cleartext = "";
        $pid = "";
        // Get PID and DATA values
        for ($i = 0; $i < count($arr); $i++) {
            if (substr(strtoupper($arr[$i]), 0, 5) == "DATA=")
                $cleartext = substr($arr[$i], 5);
            if (substr(strtoupper($arr[$i]), 0, 4) == "PID=")
                $pid = substr(strtoupper($arr[$i]), 4, 7);
        }
        // Url decoding
        $cleartext = rawurldecode($cleartext);
        // Base64
        $cleartext = base64_decode($cleartext);
        $lastc = ord($cleartext[strlen($cleartext) - 1]);
        // Unpad
        $validpad = 1;
        for ($i = 0; $i < $lastc; $i++)
            if (ord(substr($cleartext, strlen($cleartext) - 1 - $i, 1)) != $lastc)
                $validpad = 0;
        if ($validpad == 1)
            $cleartext = substr($cleartext, 0, strlen($cleartext) - $lastc);
        // 3DES
        $f = fopen($path, "r");
        $keyinfo = fread($f, 38);
        fclose($f);
        $key1 = substr($keyinfo, 14, 8);
        $key2 = substr($keyinfo, 22, 8);
        $iv = substr($keyinfo, 30, 8);
        $key = $key1 . $key2 . $key1;
        $cleartext = openssl_decrypt($cleartext, "DES-EDE3-CBC", $key, OPENSSL_RAW_DATA, $iv);
        // CRC32 check
        $crc = substr($cleartext, strlen($cleartext) - 4);
        $crch = "";
        for ($i = 0; $i < 4; $i++)
            $crch .= str_pad(dechex(ord($crc[$i])), 2, "0", STR_PAD_LEFT);
        $cleartext = substr($cleartext, 0, strlen($cleartext) - 4);
        $crc = str_pad(dechex(crc32($cleartext)), 8, "0", STR_PAD_LEFT);
        if ($crch != $crc)
            return "";
        // URL decoding
        $cleartext = str_replace("&", "%26", $cleartext);
        $cleartext = str_replace("=", "%3D", $cleartext);
        $cleartext = rawurldecode($cleartext);
        return $cleartext;
    }

    /**
     * Split CIB response
     * @param string $result
     * @return array
     */
    public function splitResult(string $result)
    {
        $returnarray = array();
        $resultarrays = explode('&', $result);
        foreach ($resultarrays as $resultarray) {
            $split = explode('=', $resultarray);
            $returnarray[$split[0]] = $split[1];
        }

        return $returnarray;
    }


}