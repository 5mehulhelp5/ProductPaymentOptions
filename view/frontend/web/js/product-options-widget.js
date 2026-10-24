/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */
define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery/ui'
], function ($, priceUtils, modal) {
    'use strict';

    /**
     * Parse a configured number, treating empty or invalid values as zero.
     *
     * @param {String|Number} value
     * @returns {Number}
     */
    function toNumber(value) {
        const number = parseFloat(value);

        return isNaN(number) ? 0 : number;
    }

    /**
     * Round a monetary amount to two decimal places.
     *
     * @param {Number} amount
     * @returns {Number}
     */
    function roundMoney(amount) {
        return Math.round((amount + Number.EPSILON) * 100) / 100;
    }

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
            const minInstallmentValue = toNumber(this.options.minInstallmentValue);

            $('.fineweb-installments').remove();

            // No usable price yet (e.g. bundle before its options are chosen): leave the rows empty
            if (!isFinite(productPrice)) {
                return;
            }

            for (const key in paymentOptions) {
                if (paymentOptions.hasOwnProperty(key)) {
                    const option = paymentOptions[key];
                    const discountFixed = toNumber(option.discount_fixed);
                    const discountPercentage = toNumber(option.discount_percentage);
                    const maxInstallments = parseInt(option.max_installments, 10);
                    const noInterestInstallments = parseInt(option.no_interest_installments, 10) || 0;
                    const monthlyInterestRate = toNumber(option.monthly_interest_rate);
                    let content = '<div class="fineweb-installments">';
                    let finalPrice = productPrice;

                    // Apply fixed or percentage discount
                    if (discountFixed > 0) {
                        finalPrice -= discountFixed;
                    } else if (discountPercentage > 0) {
                        finalPrice -= (productPrice * discountPercentage / 100);
                    }

                    // Ensure finalPrice is not less than zero and round it for display purposes
                    finalPrice = roundMoney(Math.max(finalPrice, 0));

                    // Add discount if applicable
                    if (discountFixed > 0 || discountPercentage > 0) {
                        content += '<p class="fineweb-discount-amount">' + $.mage.__('Discount: ') + this._formatCurrency(roundMoney(productPrice - finalPrice)) + '</p>';
                    }

                    // Final price
                    content += '<p class="fineweb-final-amount">' + $.mage.__('Final Price: ') + this._formatCurrency(finalPrice) + '</p>';

                    // Installments details
                    if (maxInstallments > 1) {
                        content += '<p>' + $.mage.__('Installment Options:') + '</p>';
                        for (let i = 1; i <= maxInstallments; i++) {
                            let installmentAmount;
                            if (i <= noInterestInstallments) {
                                installmentAmount = finalPrice / i;
                            } else {
                                // Calculate installment with compound monthly interest
                                const interestMultiplier = Math.pow(1 + monthlyInterestRate / 100, i);
                                installmentAmount = (finalPrice * interestMultiplier) / i;
                            }

                            // Round installmentAmount for display purposes
                            installmentAmount = roundMoney(installmentAmount);

                            // Hide installments below the minimum value
                            if (installmentAmount >= minInstallmentValue) {
                                content += '<p>' + i + ' x ' + this._formatCurrency(installmentAmount) + (i > noInterestInstallments ? ' (' + $.mage.__('with ') + monthlyInterestRate + '% ' + $.mage.__('interest per month') + ')' : '') + '</p>';
                            }
                        }
                    } else {
                        content += '<p>' + this._formatCurrency(finalPrice) + '</p>';
                    }

                    content += '</div>';

                    // Update the HTML of the corresponding LI
                    const $li = $('#payment-option' + key).find('.price-content');
                    $li.html(content);
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
            const widget = this._getPriceBoxWidget();
            const priceFormat = widget && widget.options.priceConfig && widget.options.priceConfig.priceFormat;
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
