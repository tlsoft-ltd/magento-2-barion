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

namespace TLSoft\BarionGateway\Gateway\Http;
use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use TLSoft\BarionGateway\Helper\Data;

/**
 * @property Data $helper
 * @property TransferBuilder $transferBuilder
 */
class TransferFactory implements TransferFactoryInterface
{

    /**
     * Summary of __construct
     * @param TransferBuilder $transferBuilder 
     * @param Data $helper 
     */
    public function __construct(
        TransferBuilder $transferBuilder,
		Data $helper
    ) {
        $this->transferBuilder = $transferBuilder;
		$this->helper = $helper;
    }
    /**
	 * Builds gateway transfer object
	 *
	 * @param array $request
	 * @return TransferInterface
	 */
    public function create(array $request): TransferInterface
    {
		$helper = $this->helper;

		$json = $helper->convertMessage($request);


		$url = $helper->getMarketUrl();

        return $this->transferBuilder
            ->setMethod("POST")
            ->setUri($url)
            ->setBody($json)
            ->shouldEncode(true)
            ->build();
    }
}