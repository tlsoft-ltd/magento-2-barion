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
namespace TLSoft\CibGateway\Gateway\Response;

use Magento\Sales\Model\Order\Payment;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use TLSoft\CibGateway\Helper\Data;

class RefundHandler implements HandlerInterface
{

    /**
     * @var Data
     */
    private $helper;

    /**
     * Summary of __construct
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ){
        $this->helper = $helper;
    }

    /**
     * Handles response
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        $helper = $this->helper;

        $paymentDO = $handlingSubject['payment'];

        $result = $helper->getDecodedMessage($response[0]);

        if ($paymentDO->getPayment() instanceof Payment) {

            /** @var Payment $orderPayment */
            $orderPayment = $paymentDO->getPayment();

            $orderPayment->setIsTransactionClosed(true);
            $orderPayment->setShouldCloseParentTransaction(true);
            $orderPayment->setTransactionAdditionalInfo(Transaction::RAW_DETAILS,$result);
        }
    }
}
