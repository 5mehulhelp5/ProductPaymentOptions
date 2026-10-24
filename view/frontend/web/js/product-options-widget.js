/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/modal/modal',
    'Fineweb_ProductPaymentOptions/js/installments',
    'mage/translate',
    'jquery/ui'
], function ($, priceUtils, modal, installments) {
    'use strict';

    /**
     * Widget for managing product payment options
     */
    $.widget('mage.finewebProductPaymentOptions', {
        options: {
            paymentOptions: {}, // Payment options object
            productPrice: 0, // Product price
            minInstallmentValue: 0 // Minimum installment value
        },

        modalOptions: {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $.mage.__('Payment Options'),
            buttons: []
        },

        /**
         * Widget initialization
         */
        _create: function () {
            this._updateProductPrice();
            this._updatePaymentOptions();
            this._observePriceChanges();
            this._modalInit();
        },

        /**
         * Update the product price
         * @private
         */
        _updateProductPrice: function () {
            this.options.productPrice = this._getFinalPrice();
        },

        /**
         * Recalculate whenever the price box of the product re-renders (option selection, qty, tier prices)
         * @private
         */
        _observePriceChanges: function () {
            const self = this;

            this._getPriceBox().on('reloadPrice', function () {
                self._onPriceChange(self._getFinalPrice());
            });
        },

        /**
         * Handle price change
         * @param {number} newPrice - The new product price
         * @private
         */
        _onPriceChange: function (newPrice) {
            this.options.productPrice = newPrice;
            this._updatePaymentOptions();
        },

        /**
         * Calculate and update payment options
         * @private
         */
        _updatePaymentOptions: function () {
            const paymentOptions = this.options.paymentOptions;
            const productPrice = this.options.productPrice;
            const minInstallmentValue = installments.toNumber(this.options.minInstallmentValue);

            $('.fineweb-installments').remove();

            // No usable price yet (e.g. bundle before its options are chosen): leave the rows empty
            if (!isFinite(productPrice)) {
                return;
            }

            for (const key in paymentOptions) {
                if (paymentOptions.hasOwnProperty(key)) {
                    const plan = installments.calculate(productPrice, paymentOptions[key], minInstallmentValue);
                    let content = '<div class="fineweb-installments">';

                    // Amounts are rounded to cents only here, where they are displayed
                    if (plan.hasDiscount) {
                        content += '<p class="fineweb-discount-amount">' + $.mage.__('Discount: ') + this._formatCurrency(installments.roundMoney(plan.discount)) + '</p>';
                    }

                    content += '<p class="fineweb-final-amount">' + $.mage.__('Final Price: ') + this._formatCurrency(installments.roundMoney(plan.final)) + '</p>';

                    if (plan.installments.length) {
                        content += '<p>' + $.mage.__('Installment Options:') + '</p>';
                        plan.installments.forEach(function (installment) {
                            content += '<p>' + installment.count + ' x ' + this._formatCurrency(installments.roundMoney(installment.amount)) + (installment.withInterest ? ' (' + $.mage.__('with ') + this._formatPercent(plan.monthlyRate) + '% ' + $.mage.__('interest per month') + ')' : '') + '</p>';
                        }, this);
                    }

                    content += '</div>';

                    // Update the HTML of the corresponding LI
                    $('#payment-option' + key).find('.price-content').html(content);
                }
            }
        },

        /**
         * Get the price box of the main product
         * @private
         * @returns {jQuery}
         */
        _getPriceBox: function () {
            const mainBox = $('.product-info-main [data-role="priceBox"]').first();

            return mainBox.length ? mainBox : $('[data-role="priceBox"]').first();
        },

        /**
         * Get the price box widget instance, when it is already initialized
         * @private
         * @returns {Object|undefined}
         */
        _getPriceBoxWidget: function () {
            return this._getPriceBox().data('mage-priceBox');
        },

        /**
         * Get the price format of the store, when the price box is initialized
         * @private
         * @returns {Object|undefined}
         */
        _getPriceFormat: function () {
            const widget = this._getPriceBoxWidget();

            return widget && widget.options.priceConfig && widget.options.priceConfig.priceFormat;
        },

        /**
         * Get the current final price (as a number, not parsed from the formatted text)
         * @private
         * @returns {number} NaN when the product has no final price yet
         */
        _getFinalPrice: function () {
            const widget = this._getPriceBoxWidget();
            const prices = widget && widget.cache && widget.cache.displayPrices;

            if (prices && prices.finalPrice) {
                return Object.values(prices.finalPrice.adjustments || {}).reduce(function (sum, adjustment) {
                    return sum + adjustment;
                }, prices.finalPrice.amount);
            }

            // Price box not initialized yet: fall back to the amount rendered by the server
            return parseFloat(
                this._getPriceBox().find('[data-price-type="finalPrice"]').first().attr('data-price-amount')
            );
        },

        /**
         * Format currency using the store's price format
         * @private
         * @param {number} amount - The amount to format
         * @returns {string}
         */
        _formatCurrency: function (amount) {
            const priceFormat = this._getPriceFormat();
            const currency = $('meta[itemprop="priceCurrency"]').attr('content');

            if (priceFormat) {
                return priceUtils.formatPriceLocale(amount, priceFormat);
            }

            if (currency) {
                return new Intl.NumberFormat(document.documentElement.lang || undefined, {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            }

            return amount.toFixed(2);
        },

        /**
         * Format a number such as an interest rate with the store's decimal separator ("1,99" in pt_BR)
         * @private
         * @param {number} value
         * @returns {string}
         */
        _formatPercent: function (value) {
            const priceFormat = this._getPriceFormat();

            if (priceFormat && priceFormat.decimalSymbol) {
                return String(value).replace('.', priceFormat.decimalSymbol);
            }

            return new Intl.NumberFormat(document.documentElement.lang || undefined, {
                maximumFractionDigits: 4
            }).format(value);
        },

        /**
         * Initialize modal
         * @private
         */
        _modalInit: function () {
            modal(this.modalOptions, $('#paymentOptionsModal'));

            $('#openPaymentOptionsModal').on('click', function () {
                $('#paymentOptionsModal').modal('openModal');
            });
        }
    });

    return $.mage.finewebProductPaymentOptions;
});
