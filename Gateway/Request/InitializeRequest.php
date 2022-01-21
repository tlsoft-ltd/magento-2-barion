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

namespace TLSoft\CibGateway\Gateway\Request;

use TLSoft\CibGateway\Helper\Data;
use TLSoft\CibGateway\Model\Ui\ConfigProvider;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class InitializeRequest implements BuilderInterface
{
	/**
	 * @var Data
	 */
	private $helper;

	/**
	 * @var OrderFactory
	 */
	private $order;

	/**
	 * @var ConfigProvider
	 */
    private $configProvider;

    public function __construct(
		ConfigProvider $config,
		Data $helper
    ) {
		$this->helper = $helper;
		$this->configProvider = $config;
    }

	/**
	 * Summary of getConfig
	 * @param string $path
	 * @return boolean|string
	 */
	protected function getConfig(string $path, array $config)
	{
		if($path){
			$value = $config[$path];
			return $value;
		}

		return false;
	}

	/**
	 * Get CIB merchant ID
	 * @return string
	 */
	protected function getPid(array $config)
	{
		$currency = $this->order->getCurrencyCode();
		if($currency=="HUF"){
			return $this->getConfig("pid_huf",$config);
		}else{
			return $this->getConfig("pid_eur",$config);
		}
	}

	/**
	 * Return order customer id or with a random generated number
	 * @return numeric
	 */
	protected function getUserId()
	{
		$order = $this->order;

		$customerId = $order->getCustomerId();

		if($customerId==NULL){
			$customerId = "";
			for ($i=0;$i<10;$i++)
			{
				$customerId.=rand(0,9);
			}
		}

		return $customerId;
	}


    /**
	 * Builds CIB request
	 *
	 * @param array $buildSubject
	 * @return array
	 */
    public function build(array $buildSubject)
    {
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
		$message["language_code"] = $helper->getLocaleCode($storeId);
		$message["timecode"] = $helper->getTimeCode();
		$message["pid"] = $this->getPid($providerConfig);
		$message["userid"] = $this->getUserId();
		$message["currency_code"] = $order->getCurrencyCode();
		$message["amount"] = $helper->formatOrderTotal($order->getGrandTotalAmount(),$order->getCurrencyCode());
		$message["return_url"] = $helper->getUrl("cibgateway/payment/response");
		$message["transaction_id"] = $this->getTrid(intval($order->getOrderIncrementId()));
		$message["id"]=10;

        return $message;
    }

	/**
	 * Create transaction ID
	 * @param int $orderId
	 * @return integer
	 */
	protected function getTrid(int $orderId)
	{
		$idCount = strlen($orderId);
		$total = 16-$idCount;
		$trid = $orderId;
		for ($i=1;$i<=$total; $i++)
		{
			$number=rand(1,9);
			$trid.=$number;
		}
		return $trid;
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
}