/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*jshint browser:true jquery:true*/
/*global alert*/
define(
    [
        'Magento_Checkout/js/view/summary/abstract-total',
        'Magento_Checkout/js/model/quote',
        'Magento_Catalog/js/price-utils',
        'Magento_Checkout/js/model/totals'
    ],
    function (Component, quote, priceUtils, totals) {
        "use strict";
        return Component.extend({
            defaults: {
                isFullTaxSummaryDisplayed: window.checkoutConfig.isFullTaxSummaryDisplayed || false,
                template: 'Decidir_AdminPlanesCuotas/checkout/summary/costo'
            },
            totals: quote.getTotals(),
            isTaxDisplayedInGrandTotal: window.checkoutConfig.includeTaxInGrandTotal || false,
            isDisplayed: function() {
                return this.isFullMode();
            },
            getValue: function() {
                var price = 0;
                if (this.totals()) {
                    console.log('metodo getValue = '+this.totals());
                    console.log(this.totals());

                    price = totals.getSegment('decidir_costofinanciero').value;
                    console.log('price='+price);
                }
                return this.getFormattedPrice(price);
            },
            getBaseValue: function() {
                console.log('metodo getBaseValue');
                /*
                var price = 0;
                if (this.totals()) {
                    price = this.totals().base_costo;
                }
                return priceUtils.formatPrice(price, quote.getBasePriceFormat());*/
            }
        });
    }
);