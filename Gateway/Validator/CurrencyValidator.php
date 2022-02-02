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
namespace TLSoft\BarionGateway\Gateway\Validator;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use TLSoft\BarionGateway\Helper\Data;
class CurrencyValidator extends AbstractValidator
{
	/**
	 * @var Data
	 */
	protected $helper;

	/**
	 * @param Data $helper
	 * @param \Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
	 */
	public function __construct(
		Data $helper,
		\Magento\Payment\Gateway\Validator\ResultInterfaceFactory $resultFactory
   ) {
		$this->helper = $helper;
        parent::__construct($resultFactory);
    }

    /**
     * Validate currency
     * @param array $validationSubject 
     * @return mixed
     */
    public function validate(array $validationSubject)
    {
		$currencyCode = $validationSubject["currency"];
        if (!in_array($currencyCode, $this->getAcceptedCurrencyCodes())) {
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

	/**
	 * Return enabled currency codes for payment
	 * @return array
	 */
	private function getAcceptedCurrencyCodes(){
		$helper = $this->helper;
		return $helper->getAllowedCurrencyCodes();
	}
}