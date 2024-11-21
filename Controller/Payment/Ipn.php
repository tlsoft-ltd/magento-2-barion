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
use Magento\Framework\Exception\LocalizedException;
use TLSoft\BarionGateway\Gateway\Helper\Communication;
use Magento\Framework\App\Action\Context;

/**
 * @property Communication $helper
 */
class Ipn extends Action
{
    private Communication $helper;

    /**
     * Summary of __construct
     * @param Context $context
     * @param Communication $helper
     */
    public function __construct(
        Context $context,
        Communication $helper
    ) {
        parent::__construct($context);
        $this->helper = $helper;
    }

    public function execute(): void
    {
        $urlParams = [$this->getRequest()->getParams()];

        $this->helper->processResponse($urlParams);

    }

}