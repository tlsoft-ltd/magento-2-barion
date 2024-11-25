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

namespace TLSoft\BarionGateway\Gateway\Helper;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;
use Magento\Sales\Model\Service\InvoiceService;
use TLSoft\BarionGateway\Helper\Data;
use TLSoft\BarionGateway\Model\Config\Source\ResultCodes;
use TLSoft\BarionGateway\Model\Ui\ConfigProvider;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use UnexpectedValueException;


/**
 * @property Data $helper
 * @property ManagerInterface $messageManager
 * @property ConfigProvider $configProvider
 * @property OrderInterface $orderRepository
 * @property OrderManagementInterface $orderManagement
 * @property OrderSender $orderSender
 * @property AuthorizeCommand $authorizeCommand
 * @property FilterGroupBuilder $filterGroup
 * @property SearchCriteriaBuilder $searchCriteria
 * @property FilterBuilder $filterBuilder
 * @property InvoiceService $invoiceService
 * @property TransactionFactory $transactionFactory
 * @property BuilderInterface $transactionBuilder
 * @property Session $checkoutSession
 * @property TransactionRepositoryInterface $transactionRepository
 * @property string $responseCode
 */
class Communication extends AbstractHelper
{

    private string $responseCode;
    private OrderSender $orderSender;
    private AuthorizeCommand $authorizeCommand;
    private SearchCriteriaBuilder $searchCriteria;
    private FilterGroupBuilder $filterGroup;
    private FilterBuilder $filterBuilder;
    private TransactionRepositoryInterface $transactionRepository;
    private Session $checkoutSession;
    private BuilderInterface $transactionBuilder;
    private TransactionFactory $transactionFactory;
    private InvoiceService $invoiceService;
    private OrderManagementInterface $orderManagement;
    private ConfigProvider $configProvider;
    private OrderRepositoryInterface $orderRepository;
    private ManagerInterface $messageManager;
    private Data $helper;

    public function __construct(
        Context                        $context,
        Data                           $helper,
        ManagerInterface               $messageManager,
        OrderInterface                 $order,
        ConfigProvider                 $configProvider,
        OrderManagementInterface       $orderManagement,
        InvoiceService                 $invoiceService,
        TransactionFactory             $transactionFactory,
        BuilderInterface               $transactionBuilder,
        TransactionRepositoryInterface $transactionRepository,
        FilterBuilder                  $filterBuilder,
        FilterGroupBuilder             $filterGroupBuilder,
        SearchCriteriaBuilder          $searchCriteriaBuilder,
        Session                        $checkoutSession,
        AuthorizeCommand $authorizeCommand,
        OrderRepositoryInterface $orderRepository,
        OrderSender $orderSender
    )
    {
        parent::__construct($context);
        $this->helper = $helper;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->configProvider = $configProvider;
        $this->orderManagement = $orderManagement;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->transactionBuilder = $transactionBuilder;
        $this->checkoutSession = $checkoutSession;
        $this->transactionRepository = $transactionRepository;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroupBuilder;
        $this->searchCriteria = $searchCriteriaBuilder;
        $this->authorizeCommand = $authorizeCommand;
        $this->orderSender = $orderSender;
    }

    /**
     * Get response code from the Gateway
     * @param array $params
     * @return Communication
     */
    public function processResponse(array $params): Communication
    {
        $this->processTransaction($params);
        return $this;
    }

    public function processTransaction($params = array(), $orderComplete = true, $order = '')
    {
        $orderManagement = $this->orderManagement;

        if (!$params && !$orderComplete) {
            $orderManagement->cancel($order->getId());

            $status = $orderManagement->getStatus($order->getId());
            $order->addCommentToStatusHistory(__('Closed by Barion module.') . ' ' . __('Closed by cron.'), $status);

            return;

        }

        $helper = $this->helper;
        $methodCode = "bariongateway";

        if(array_key_exists("paymentId",$params)){
            $transactionId = $params["paymentId"];
        }else if(array_key_exists("paymentId",$params[0])){
            $transactionId = $params[0]["paymentId"];
            $filter = [['field' => 'txn_id', 'value' => $transactionId, 'condition' => 'eq']];
            $criteria = $this->getSearchCriteria($filter);

            if (is_object($criteria)) {
                $transactionRepository = $this->transactionRepository;
                $result = $transactionRepository->getList($criteria);
                $transaction = $result->getFirstItem();
                $orderId = $transaction->getOrderId();
                $order = $this->orderRepository->get($orderId);
            }
        }

        if (!$order)
            $order = $this->checkoutSession->getLastRealOrder();

        if ($params && $orderComplete) {
            $increment_id = $order->getId();
            $filter = [['field' => 'order_id', 'value' => $increment_id, 'condition' => 'eq']];
            $criteria = $this->getSearchCriteria($filter);

            if (is_object($criteria)) {
                $transactionRepository = $this->transactionRepository;
                $result = $transactionRepository->getList($criteria);
                $transactions = $result->getItems();
                $i = 0;
                if ($result->getTotalCount() > 0) {
                    foreach ($transactions as $transaction) {
                        if ($i > 0 || $transaction->getTxnType() == TransactionInterface::TYPE_CAPTURE) {
                            $this->responseCode = ResultCodes::RESULT_SUCCESS;
                            return $this;
                        }
                        $i++;
                    }
                }
            }
        }

        $config = $this->getProviderConfig($methodCode);

        $params = "?POSKey=" . $this->getPosKey($config) . "&PaymentId=" . $transactionId;

        $this->responseCode = ResultCodes::RESULT_PENDING;

        $resulttext = __('Transaction ID') . ": " . $transactionId . "\n";

        $response = $this->cURL($helper->getStateUrl() . $params);

        if ($response) {

            $payment = $order->getPayment();

            //@todo Handle response information.

            $result = $helper->getDecodedMessage($response);

            unset($result["AllowedFundingSources"]);
            unset($result["Transactions"]);
            unset($result["FundingInformation"]);

            if (count($result["Errors"])<1) {
                unset($result["Errors"]);
                if ($result['Status'] == "Succeeded") {
                    $resulttext .= __('Authorization number') . ": " . $result['PaymentId'];
                    $this->responseCode = ResultCodes::RESULT_SUCCESS;
                    $this->messageManager->addSuccessMessage($resulttext);
                    $payment->setIsTransactionApproved(true);
                    $payment->setLastTransId($transactionId);
                    $payment->setAdditionalInformation([Transaction::RAW_DETAILS => $result]);

                    $transaction = $this->transactionBuilder->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($transactionId)
                        ->setFailSafe(true)
                        ->setAdditionalInformation([Transaction::RAW_DETAILS => $result])
                        ->build(TransactionInterface::TYPE_CAPTURE);

                    if ($transaction) {
                        $this->transactionRepository->save(
                            $transaction
                        );
                    }

                    $this->orderSender->send($order);

                } elseif ($result['Status'] == "Canceled") {//returned by user - cancel transaction
                    $this->responseCode = ResultCodes::RESULT_USER_CANCEL;
                    $this->messageManager->addErrorMessage($resulttext);

                    $payment->setIsTransactionClosed(true);
                    $payment->setShouldCloseParentTransaction(true);

                    $transaction = $this->transactionBuilder->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($transactionId)
                        ->setFailSafe(true)
                        ->setAdditionalInformation([Transaction::RAW_DETAILS => $result])
                        ->build(TransactionInterface::TYPE_ORDER);

                    if ($transaction) {
                        $this->transactionRepository->save(
                            $transaction
                        );
                    }

                    $orderManagement->cancel($order->getId());

                    $status = $orderManagement->getStatus($order->getId());
                    $order->addCommentToStatusHistory(__('Closed by Barion module.'), $status);
                } elseif ($result['Status']=="Prepared"||$result['Status']=="Started") {

                    $payment->setIsTransactionClosed(false);

                    $transaction = $this->transactionBuilder->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($transactionId)
                        ->setFailSafe(true)
                        ->setAdditionalInformation([Transaction::RAW_DETAILS => $result])
                        ->build(TransactionInterface::TYPE_ORDER);

                    if ($transaction) {
                        $this->transactionRepository->save(
                            $transaction
                        );
                    }

                    $this->responseCode = ResultCodes::RESULT_PENDING;
                } else {

                    $payment->setIsTransactionClosed(true);

                    $transaction = $this->transactionBuilder->setPayment($payment)
                        ->setOrder($order)
                        ->setTransactionId($transactionId)
                        ->setFailSafe(true)
                        ->setAdditionalInformation([Transaction::RAW_DETAILS => $result])
                        ->build(TransactionInterface::TYPE_ORDER);

                    if ($transaction) {
                        $this->transactionRepository->save(
                            $transaction
                        );
                    }

                    $this->responseCode = ResultCodes::RESULT_ERROR;
                    $orderManagement->cancel($order->getId());

                    $status = $orderManagement->getStatus($order->getId());
                    $order->addCommentToStatusHistory(__('Closed by Barion module.'), $status);

                    $this->messageManager->addErrorMessage($resulttext);
                }
            }else{
                $error = $helper->convertMessage($result["Errors"]);
                $result["Errors"] = $error;
                $payment->setIsTransactionClosed(false);

                $transaction = $this->transactionBuilder->setPayment($payment)
                    ->setOrder($order)
                    ->setTransactionId($transactionId)
                    ->setFailSafe(true)
                    ->setAdditionalInformation([Transaction::RAW_DETAILS => $result])
                    ->build(TransactionInterface::TYPE_ORDER);

                if ($transaction) {
                    $this->transactionRepository->save(
                        $transaction
                    );
                }

                $this->responseCode = ResultCodes::RESULT_PENDING;
            }
        } else {
            $orderManagement->cancel($order->getId());

            $status = $orderManagement->getStatus($order->getId());
            $order->addCommentToStatusHistory(__('Closed by Barion module. CURL error.') . '-' . $response, $status);

            $this->responseCode = ResultCodes::RESULT_ERROR;
        }

        return $this;
    }

    /**
     * Get search criteria for collection filtering
     * @param array $criteria
     * @return SearchCriteria|null
     */
    private function getSearchCriteria($criteria = array())
    {
        $search = "";
        $groups = array();
        foreach ($criteria as $crit) {
            $filter = $this->getFilter($crit);
            if (is_object($filter)) {
                $group = $this->getFilterGroup($filter);
                $groups[] = $group;
            }
        }
        if (count($groups) > 0) {
            $search = $this->searchCriteria;
            $search->setFilterGroups($groups);
            $search = $search->create();
        }
        return $search;
    }

    /**
     * Get filter for search criteria
     * @param array $criteria
     * @return null|Filter
     */
    private function getFilter($criteria = array())
    {
        $filter = "";
        if (count($criteria) > 0) {
            $filter = clone $this->filterBuilder;
            $filter->setField($criteria["field"]);
            $filter->setValue($criteria["value"]);
            $filter->setConditionType($criteria["condition"]);
            $filter = $filter->create();
        }
        return $filter;
    }

    /**
     * Get Filter grouo for the search criteria builder
     * @param Filter $filter
     */
    private function getFilterGroup(Filter $filter)
    {
        $group = clone $this->filterGroup;
        $group->addFilter($filter);
        return $group->create();
    }

    protected function getProviderConfig(string $payment): array
    {
        return $this->configProvider->getProviderConfig($payment);
    }

    protected function getPosKey(array $config)
    {
        return $this->getConfig("poskey", $config);
    }

    /**
     * Summary of getConfig
     * @param string $path
     * @param array $config
     * @return boolean|string
     */
    protected function getConfig(string $path, array $config)
    {
        if ($path) {
            return $config[$path];
        }

        return false;
    }

    protected function cURL($url)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($userAgent == "") {
            $cver = curl_version();
            $userAgent = "curl/" . $cver["version"] . " " .$cver["ssl_version"];
        }
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => 0,    // don't return headers
            CURLOPT_FOLLOWLOCATION => 0,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => $userAgent, // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
            CURLOPT_TIMEOUT => 30,      // timeout on response
            CURLOPT_MAXREDIRS => 5,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_SSL_VERIFYHOST => false
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $retValue = curl_exec($ch);
        $error = curl_error($ch);
        if ($retValue === FALSE) {
            $error = curl_error($ch);
            curl_close($ch);
            return false;
        } else {
            curl_close($ch);
            return $retValue;
        }
    }

    public function cURLPost($url,$data)
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if ($userAgent == "") {
            $cver = curl_version();
            $userAgent = "curl/" . $cver["version"] . " " .$cver["ssl_version"];
        }
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER => 0,    // don't return headers
            CURLOPT_FOLLOWLOCATION => 0,     // follow redirects
            CURLOPT_ENCODING => "",       // handle all encodings
            CURLOPT_USERAGENT => $userAgent, // who am i
            CURLOPT_AUTOREFERER => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 30,      // timeout on connect
            CURLOPT_TIMEOUT => 30,      // timeout on response
            CURLOPT_MAXREDIRS => 5,       // stop after 10 redirects
            CURLOPT_SSL_VERIFYPEER => false,
            CURLINFO_HEADER_OUT => 1,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','Content-Length: '.strlen($data)],
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $data
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $retValue = curl_exec($ch);
        $error = curl_error($ch);
        if ($retValue === FALSE) {
            $error = curl_error($ch);
            curl_close($ch);
            return false;
        } else {
            curl_close($ch);
            return $this->helper->getDecodedMessage($retValue);
        }
    }

    public function processRefundStart($trid, $pid, $amount, $total, $payment): bool
    {
        $helper = $this->helper;

        $message = [];

        $message['pid'] = $pid;
        $message['trid'] = $trid;
        $message['amo'] = $total;
        $msg = 70;

        $urlend = $helper->convertMessage($message, $msg);

        $url = $helper->getMarketUrl() . "?" . $urlend;

        $response = $this->cURL($url);

        $result = $helper->getDecodedMessage($response);

        if ($result['STATUS'] == "20" || $result['STATUS'] == "30") {
            $message = [];
            $message['pid'] = $pid;
            $message['trid'] = $trid;
            $message['amoorig'] = $result['CURAMO2'];
            $message['amonew'] = $amount;

            $msg = 80;
            $urlend = $helper->convertMessage($message, $msg);

            $url = $helper->getMarketUrl() . "?" . $urlend;

            $response = $this->cURL($url);

            $result = $helper->getDecodedMessage($response);

            if ($result['STATUS'] == 99)
                throw new UnexpectedValueException('An error occurred during processing.');

        } else if ($result['STATUS'] == 60) {
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
            throw new UnexpectedValueException('The transaction has been already closed.');
        } else if ($result['STATUS'] == 10) {
            return false;
        } else {
            if ($amount != $total):
                throw new UnexpectedValueException('Partial refund cannot completed yet. Wait for the bank closing hour.');
            else:
                throw new UnexpectedValueException('Status: ' . $result['STATUS'] . '-' . $result['RT'] . ' - ' . $result['TRID']);
            endif;

        }

        return true;

    }

    /**
     * Transaction response code.
     * @return string
     */
    public function getCode(): string
    {
        return $this->responseCode;
    }


}