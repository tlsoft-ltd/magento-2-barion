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

namespace TLSoft\CibGateway\Gateway\Validator;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use TLSoft\CibGateway\Helper\Data;
class AvailabilityValidator extends AbstractValidator
{
	protected $_dataHelper;
    /**
	 * Performs validation of result code
	 *
	 * @param array $validationSubject
	 * @return ResultInterface
	 */

	public function __construct(
		ResultInterfaceFactory $resultFactory,
		StoreManagerInterface $storeManager,
        ScopeConfigInterface $config,
		Data $dataHelper
   ) {
        parent::__construct($resultFactory);
		$this->store = $storeManager->getStore();
        $this->config = $config;
		$this->_dataHelper = $dataHelper;
    }

    public function validate(array $validationSubject)
    {
		$merchant_key_huf = $this->config->getValue('payment/cibgateway/pid_huf');
		$merchant_key_eur = $this->config->getValue('payment/cibgateway/pid_eur');

		return $this->createResult(
                true,
                []
            );

		$enabled = false;
        if ($enabled==false) {
            return $this->createResult(
                false,
                [__('Currency not accepted.')]
            );
        }else{
			return $this->createResult(
                true,
                []
            );
		}
    }
}