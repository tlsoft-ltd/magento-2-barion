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

namespace TLSoft\BarionGateway\Gateway\Request;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use TLSoft\BarionGateway\Helper\Data;
use TLSoft\BarionGateway\Model\Ui\ConfigProvider;

class InitializeRequest implements BuilderInterface
{
    /**
     * @var Data
     */
    private $helper;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        ConfigProvider $config,
        Data           $helper
    )
    {
        $this->helper = $helper;
        $this->configProvider = $config;
    }

    /**
     * Builds Barion request
     *
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('Psr\Log\LoggerInterface');
        $logger->debug(var_export('itt',true));
        if (!isset($buildSubject['payment'])
            || !$buildSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /** @var PaymentDataObjectInterface $payment */
        $payment = $buildSubject['payment'];

        /** @var OrderAdapterInterface $order */
        $order = $payment->getOrder();
        $this->order = $order;

        $providerConfig = $this->getProviderConfig($payment);

        if (empty($providerConfig)) {
            throw new \UnexpectedValueException('Payment method is disabled or connection data is missing.');
        }

        $helper = $this->helper;
        $message = array();
        $storeId = $order->getStoreId();

        $message["POSKey"] = $this->getConfig("poskey", $providerConfig);
        $message["PaymentType"] = "Immediate"; //Accepted values: "Immediate" "Reservation" "DelayedCapture"
        $message["PaymentWindow"] = "00:15:00"; //Barion Payment window (min 1 minute - max one week)
        $message["GuestCheckout"] = true; //Flag indicating whether the payment can be completed without a registered Barion wallet.
        $message["FundingSources"] = ["All"]; //Payment can be completed with all funding sources. Equivalent to ["Balance", "Card", "GooglePay", "ApplePay"]
        $message["PaymentRequestId"] = $order->getOrderIncrementId();

        $shippingAddress = $order->getShippingAddress();
        $billingAddress = $order->getBillingAddress();

        $message["PayerHint"] = $billingAddress->getEmail();
        $message["CardHolderNameHint"] = $billingAddress->getFirstname()." ".$billingAddress->getLastname();

        $message["RedirectUrl"] = $helper->getUrl("bariongateway/payment/response");
        $message["CallbackUrl"] = $helper->getUrl("bariongateway/payment/ipn");

        $i = 0;
        $products = [];
        $items = $order->getItems();
        foreach ($items as $item) {
            $products[$i]["Name"] = $item->getName();
            $products[$i]["Description"] = $item->getName();
            $products[$i]["Quantity"] = number_format($item->getQtyOrdered(), 0);
            $products[$i]["Unit"] = "db";
            $products[$i]["UnitPrice"] = $helper->formatOrderTotal($item->getPriceInclTax(), $order->getCurrencyCode());
            $products[$i]["ItemTotal"] = $helper->formatOrderTotal($item->getRowTotalInclTax(), $order->getCurrencyCode());
            $products[$i]["SKU"] = $item->getSku();
            $i++;
        }

        $message["Transactions"] = [[
            "POSTransactionId" => $order->getOrderIncrementId(),
            "Payee" => $this->getConfig("email", $providerConfig),
            "Total" => $helper->formatOrderTotal($order->getGrandTotalAmount(), $order->getCurrencyCode()),
            "Items" => $products
        ]];

        $message["OrderNumber"]=$order->getOrderIncrementId();


        $message["ShippingAddress"] = [[
            "Country" => $shippingAddress->getCountryId(),
            "City" => $shippingAddress->getCity(),
            "Zip" => $shippingAddress->getPostcode(),
            "Street" => $shippingAddress->getStreetLine1(),
            "Street2" => $shippingAddress->getStreetLine2(),
            "FullName" => $shippingAddress->getFirstname()." ".$shippingAddress->getLastname()
        ]];

        $message["Locale"] = $helper->getLocaleCode($storeId);
        $message["Currency"] = $order->getCurrencyCode();

        $message["BillingAddress"] = [[
            "Country" => $billingAddress->getCountryId(),
            "City" => $billingAddress->getCity(),
            "Zip" => $billingAddress->getPostcode(),
            "Street" => $billingAddress->getStreetLine1(),
            "Street2" => $billingAddress->getStreetLine2(),
            "FullName" => $billingAddress->getFirstname()." ".$billingAddress->getLastname()
        ]];

        $message["PayerAccountInformation"] = [["SuspiciousActivityIndicator" => "NoSuspiciousActivityObserved"]];
        $message["ChallengePreference"] = "NoPreference";

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $logger = $objectManager->get('Psr\Log\LoggerInterface');
        $logger->debug(var_export($message,true));

        return $message;
    }

    /**
     * @param PaymentDataObjectInterface $payment
     * @return array
     * @throws LocalizedException
     */
    protected function getProviderConfig(PaymentDataObjectInterface $payment)
    {
        $methodCode = $payment->getPayment()->getMethodInstance()->getCode();

        return $this->configProvider->getProviderConfig($methodCode);
    }

    /**
     * Summary of getConfig
     * @param string $path
     * @return boolean|string
     */
    protected function getConfig(string $path, array $config)
    {
        if ($path) {
            $value = $config[$path];
            return $value;
        }

        return false;
    }
}