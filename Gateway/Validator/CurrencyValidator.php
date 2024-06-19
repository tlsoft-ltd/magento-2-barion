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
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use TLSoft\BarionGateway\Helper\Data;
class CurrencyValidator extends AbstractValidator
{
	/**
	 * @var Data
	 */
	protected $helper;

	/**
	 * @param Data $helper
	 * @param ResultInterfaceFactory $resultFactory
	 */
	public function __construct(
		Data $helper,
		ResultInterfaceFactory $resultFactory
   ) {
		$this->helper = $helper;
        parent::__construct($resultFactory);
    }

    /**
     * Validate currency
     * @param array $validationSubject 
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
		$currencyCode = $validationSubject["currency"];
        if (!in_array($currencyCode, $this->getAcceptedCurrencyCodes())) {
            return $this->createResult(
                false,
                [__('Currency not accepted.')]
            );
        }else{
			return $this->createResult(
                true
            );
		}
    }

	/**
	 * Return enabled currency codes for payment
	 * @return array
	 */
	private function getAcceptedCurrencyCodes(): array
    {
		$helper = $this->helper;
		return $helper->getAllowedCurrencyCodes();
	}
}