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
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component,url) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'TLSoft_BarionGateway/payment/form',
                redirectAfterPlaceOrder: false
            },

            getCode: function () {
                return 'bariongateway';
            },

            /** Returns payment cib logo path */
            getLogoSrc: function () {
                return require.toUrl('TLSoft_BarionGateway/images/logo.png');
            },

            afterPlaceOrder: function () {
                window.location.replace(url.build('bariongateway/payment/index'));
            }
        });
    }
);