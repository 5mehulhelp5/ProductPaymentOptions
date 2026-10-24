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
    'jquery-ui-modules/widget'
], function ($, priceUtils, modal, installments, $t) {
    'use strict';

    /**
     * Fill the %1, %2... placeholders of a (translated) phrase.
     *
     * @param {String} phrase
     * @param {Array} values
     * @returns {String}
     */
    function format(phrase, values) {
        return phrase.replace(/%(\d+)/g, function (placeholder, index) {
            return String(values[index - 1]);
        });
    }

    /**
     * Widget that shows the discount and the installments of each payment method of the product
     */
    $.widget('mage.finewebProductPaymentOptions', {
        options: {
            paymentOptions: {}, // Configured rows, by key
            productPrice: 0, // Product price
            minInstallmentValue: 0 // Minimum installment value
        },

        modalOptions: {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: $t('Payment Options'),
            buttons: []
        },

        /**
         * Widget initialization
         */
        _create: function () {
            // Taken before the modal widget moves the element out of this widget's element
            this.$modal = this.element.find('[data-role="payment-options-modal"]');

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
            this._getPriceBox().on('reloadPrice', function () {
                this._onPriceChange(this._getFinalPrice());
            }.bind(this));
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
         * Calculate and render the plan of every payment option
         * @private
         */
        _updatePaymentOptions: function () {
            const productPrice = this.options.productPrice;
            const minInstallmentValue = installments.toNumber(this.options.minInstallmentValue);

            this.$modal.find('[data-role="payment-option-prices"]').empty();

            // No usable price yet (e.g. bundle before its options are chosen): leave the rows empty
            if (!isFinite(productPrice)) {
                return;
            }

            this.$modal.find('[data-role="payment-option"]').each(function (index, element) {
                const $option = $(element);
                const option = this.options.paymentOptions[$option.attr('data-option-key')];

                if (option) {
                    $option.find('[data-role="payment-option-prices"]').append(
                        this._renderPlan(installments.calculate(productPrice, option, minInstallmentValue))
                    );
                }
            }.bind(this));
        },

        /**
         * Build the plan of one payment option. Amounts are rounded to cents only here, where they are displayed.
         *
         * @private
         * @param {Object} plan - Result of installments.calculate()
         * @returns {jQuery}
         */
        _renderPlan: function (plan) {
            const $plan = $('<div></div>').addClass('fineweb-payment-options__plan');
            const money = function (amount) {
                return this._formatCurrency(installments.roundMoney(amount));
            }.bind(this);

            if (plan.hasDiscount) {
                $plan.append(this._renderLine('discount', $t('Discount: %1'), [money(plan.discount)]));
            }

            $plan.append(this._renderLine('final', $t('Final Price: %1'), [money(plan.final)]));

            if (plan.installments.length) {
                $plan.append(this._renderLine('installments-title', $t('Installment Options:'), []));
                plan.installments.forEach(function (installment) {
                    $plan.append(this._renderInstallment(installment, plan.monthlyRate, money));
                }, this);
            }

            return $plan;
        },

        /**
         * Build one installment line, with the interest only when the installment has it
         *
         * @private
         * @param {Object} installment
         * @param {Number} monthlyRate
         * @param {Function} money
         * @returns {jQuery}
         */
        _renderInstallment: function (installment, monthlyRate, money) {
            const amount = money(installment.amount);
            const text = installment.withInterest ?
                format($t('%1 x %2 (with %3% interest per month)'), [
                    installment.count,
                    amount,
                    this._formatPercent(monthlyRate)
                ]) :
                format($t('%1 x %2'), [installment.count, amount]);

            return $('<p></p>').addClass('fineweb-payment-options__installment').text(text);
        },

        /**
         * Build a text line of the plan
         *
         * @private
         * @param {String} name - BEM element name
         * @param {String} phrase - Translated phrase with placeholders
         * @param {Array} values
         * @returns {jQuery}
         */
        _renderLine: function (name, phrase, values) {
            return $('<p></p>').addClass('fineweb-payment-options__' + name).text(format(phrase, values));
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
            modal(this.modalOptions, this.$modal);

            this.element.find('[data-action="open-payment-options"]').on('click', function () {
                this.$modal.modal('openModal');
            }.bind(this));
        }
    });

    return $.mage.finewebProductPaymentOptions;
});
