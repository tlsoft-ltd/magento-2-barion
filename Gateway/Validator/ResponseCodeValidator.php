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
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use TLSoft\CibGateway\Helper\Data;
class ResponseCodeValidator extends AbstractValidator
{
	/**
	 * Result code;
	 */
	const RESULT_CODE = "RC";

	const MSGT = "MSGT";

    /**
     * Status;
     */
    const STATUS = "STATUS";

	/**
	 * @var Data
	 */
    private $helper;

	/**
	 * @var ResultInterfaceFactory
	 */
    private $resultInterfaceFactory;

    /**
     * @var $errors
     */
    private $errors=[];

    /**
     * @var $codes
     */
    private $codes=[];

	/**
	 * Summary of __construct
	 * @param Data $helper
	 */
	public function __construct(
		Data $helper,
		ResultInterfaceFactory $resultFactory
		){
		$this->helper = $helper;

		parent::__construct($resultFactory);
	}

    /**
	 * Performs validation of result code
	 *
	 * @param array $validationSubject
	 * @return ResultInterface
	 */
    public function validate(array $validationSubject)
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new \InvalidArgumentException('Response does not exist');
        }
        $response = $validationSubject['response'];
        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true,
                []
            );
        } else {
            return $this->createResult(
                false,
                array_merge([__('Gateway rejected the transaction.')],$this->errors),
                $this->codes
            );
        }
    }

    /**
	 * @param array $response
	 * @return bool
	 */
    private function isSuccessfulTransaction(array $response)
    {
		$helper = $this->helper;

		$result = $helper->getDecodedMessage($response[0]);

		if(array_key_exists(self::RESULT_CODE,$result)){
			if($result[self::RESULT_CODE]=="00"){
				return true;
			}
		}else if(array_key_exists(self::STATUS,$result)){
            if($result[self::STATUS]=="50"||$result[self::STATUS]=="40"){
                return true;
            }
        }

        return false;
    }
}