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

use TLSoft\BarionGateway\Helper\Data;
use TLSoft\BarionGateway\Model\Ui\ConfigProvider;
use TLSoft\BarionGateway\Gateway\Helper\Communication;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;

class RefundRequest implements BuilderInterface
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
        Data $helper,
        Communication $communication
    ) {
        $this->helper = $helper;
		$this->configProvider = $config;
		$this->communicationHelper = $communication;
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
        $subject = $buildSubject['payment'];

        $payment = $subject->getPayment();

		/** @var OrderAdapterInterface $order */
        $order = $subject->getOrder();
		$this->order = $order;

		$providerConfig = $this->getProviderConfig($subject);

        if (empty($providerConfig)) {
            throw new \UnexpectedValueException('Payment method is disabled or connection data is missing.');
        }

        $helper = $this->helper;

        $total = $helper->formatOrderTotal($order->getGrandTotalAmount(),$order->getCurrencyCode());
        $amount = $helper->formatOrderTotal($buildSubject['amount'],$order->getCurrencyCode());

		$message = array();
		$message["pid"] = $this->getPid($providerConfig);
		$message["trid"] = $payment->getParentTransactionId();

		$result = $this->communicationHelper->processRefundStart($message['trid'],$message['pid'],$amount,$total,$payment);

		if($result == true)
		    $message["id"]=78;
		else
		    $message["id"]=74;
		$message["amo"] = $total;

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
}