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

namespace TLSoft\CibGateway\Model\Config\Source;

class ResultCodes
{

	/**
	 * Result code for timeouted transaction
	 */
	const RESULT_TIMEOUT = "timeout";

	/**
	 * Result code for transaction with error
	 */
	const RESULT_ERROR = "communication_error";

	/**
	 * Result code for transaction cancelled by an user
	 */
	const RESULT_USER_CANCEL = "user_cancel";

	/**
	 * Result code for timeouted transaction
	 */
	const RESULT_PENDING = "timeout";

	/**
	 * Result code for successful transaction
	 */
	const RESULT_SUCCESS = "success";

}