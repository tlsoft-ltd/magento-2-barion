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
use InvalidArgumentException;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
class ResponseCodeValidator extends AbstractValidator
{

    /**
     * Status;
     */
    const STATUS = "Status";

    /**
     * @var $errors
     */
    private $errors = [];

    /**
     * @var $codes
     */
    private $codes = [];

    /**
     * Summary of __construct
     * @param Data $helper
     * @param ResultInterfaceFactory $resultFactory
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory
    )
    {

        parent::__construct($resultFactory);
    }

    /**
     * Performs validation of result code
     *
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject): ResultInterface
    {
        if (!isset($validationSubject['response']) || !is_array($validationSubject['response'])) {
            throw new InvalidArgumentException('Response does not exist');
        }
        $response = $validationSubject['response'];
        if ($this->isSuccessfulTransaction($response)) {
            return $this->createResult(
                true
            );
        } else {
            return $this->createResult(
                false,
                array_merge([__('Gateway rejected the transaction.')], $this->errors),
                $this->codes
            );
        }
    }

    /**
     * @param array $response
     * @return bool
     */
    private function isSuccessfulTransaction(array $response): bool
    {

        if (array_key_exists(self::STATUS, $response)) {
            if ($response[self::STATUS] == "Prepared") {
                return true;
            }
        } else {
            foreach ($response["Errors"] as $error) {
                $this->errors[] = $error["Title"]."|".$error["Description"];
            }

            return false;
        }
    }
}