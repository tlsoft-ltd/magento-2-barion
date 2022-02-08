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

namespace TLSoft\BarionGateway\Gateway\Response;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use TLSoft\BarionGateway\Helper\Data;
use Magento\Customer\Model\Session\Storage as Session;
use Magento\Sales\Model\Order\Payment\Transaction;
class ResponseHandler implements HandlerInterface
{
    /**
     * Transaction ID
     */
    const TXN_ID = "PaymentId";

    /**
     * @var Data
     */
    private $helper;

    /**
     * @var Session
     */
    private $customerSession;

    /**
     * Summary of __construct
     * @param Data $helper
     */
    public function __construct(
        Data $helper,
        Session $customerSession
    ){

        $this->helper = $helper;
        $this->customerSession = $customerSession;
    }
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @return void
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $handlingSubject['payment'];
        $payment = $paymentDO->getPayment();

        $payment->getOrder()->setCanSendNewEmailFlag(false);

        $helper = $this->helper;

        $result = $helper->getDecodedMessage($response[0]);

        $url = $helper->getCustomerUrl()."?id=".$result["PaymentId"];

        $customerSession = $this->customerSession;
        $customerSession->setRedirectUrl($url);
        $customerSession->setTransactionId($result[self::TXN_ID]);

        $payment->setTransactionId($result[self::TXN_ID]);
        $payment->setIsTransactionClosed(false);

        $order = $payment->getOrder();

        $payment->setIsTransactionPending(true);

        $payment->addTransaction(Transaction::TYPE_AUTH,null,true);

        $order->save();

    }
}