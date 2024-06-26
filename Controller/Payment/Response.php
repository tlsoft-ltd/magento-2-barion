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

namespace TLSoft\BarionGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use TLSoft\BarionGateway\Model\Config\Source\ResultCodes;
use TLSoft\BarionGateway\Gateway\Helper\Communication;
use Magento\Framework\App\Action\Context;

/**
 * @property Session $session
 * @property Communication $helper
 * @property ManagerInterface $_messageManager
 */
class Response extends Action
{
    private Session $session;
    private Communication $helper;
    private ManagerInterface $_messageManager;

    /**
     * Summary of __construct
     * @param Context $context
     * @param Communication $helper
     * @param Session $session
     */
	public function __construct(
	Context $context,
	Communication $helper,
	Session $session
	) {
		parent::__construct($context);
		$this->session = $session;
		$this->helper = $helper;
		$this->_messageManager = $context->getMessageManager();
	}

	public function execute(){
		$urlParams = $this->getRequest()->getParams();

        $result = $this->helper->processResponse($urlParams);

        switch ($result->getCode()) {
            case ResultCodes::RESULT_TIMEOUT:
            case ResultCodes::RESULT_ERROR:
                $this->_messageManager->addErrorMessage(__("Error occured during payment."));
                $this->session->restoreQuote();
				$this->_redirect('checkout/onepage/failure', ['_secure' => true]);
				break;
            case ResultCodes::RESULT_USER_CANCEL:
                $this->_messageManager->addErrorMessage(__("The customer cancelled the payment."));
                $this->session->restoreQuote();
                $this->_redirect('checkout/onepage/failure', ['_secure' => true]);
                break;
            case ResultCodes::RESULT_PENDING:
                $this->_messageManager->addErrorMessage(__("The payment processing is pending."));
				$this->_redirect('checkout/onepage/success', ['_secure' => true]);
				break;
            case ResultCodes::RESULT_SUCCESS:
                $this->_redirect('checkout/onepage/success', ['_secure' => true]);
                break;
            default:
                throw new LocalizedException(__('Missing or invalid result code.'));
        }
	}

}