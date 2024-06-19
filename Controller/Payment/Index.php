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

use Magento\Customer\Model\Session\Storage as Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * @property Session $customerSession
 */
class Index extends Action
{
    private Session $customerSession;

    /**
     * Summary of __construct
     * @param Context $context
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        Session $customerSession
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
    }

    /**
     * @return bool|ResultInterface
     */
    public function execute()
    {
        $customerSession = $this->customerSession;
        $redirectUrl = $customerSession->getRedirectUrl();

        if ($redirectUrl) {
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($redirectUrl);
            return $resultRedirect;
        }

        return false;
    }
}