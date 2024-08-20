/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © 2024 Fineweb
 * @author      Roni Clei Santos
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery/ui'
], function($, modal) {
    'use strict';

    /**
     * Widget for managing product payment options
     */
    $.widget('mage.finewebProductPaymentOptions', {
        options: {
            paymentOptions: {}, // Payment options object
            productPrice: 0 // Product price
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
        _create: function() {
            this._updateProductPrice();
            this._updatePaymentOptions();
            this._observePriceChanges();
            this._modalInit();
        },

        /**
         * Update the product price
         * @private
         */
        _updateProductPrice: function() {
            this.options.productPrice = this._getFinalPrice();
        },

        /**
         * Observe changes in the final price
         * @private
         */
        _observePriceChanges: function() {
            var self = this;
            var priceBox = $('[data-role="priceBox"]');

            if (priceBox.length) {
                priceBox.each(function() {
                    self._startObservingPriceBox($(this));
                });
            }

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        $(mutation.addedNodes).each(function() {
                            var newPriceBoxes = $(this).find('[data-role="priceBox"]');
                            if (newPriceBoxes.length) {
                                newPriceBoxes.each(function() {
                                    self._startObservingPriceBox($(this));
                                });
                            }
                        });
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        /**
         * Start observing the priceBox element
         * @param {jQuery} priceBox - The price box element
         * @private
         */
        _startObservingPriceBox: function(priceBox) {
            var self = this;
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'characterData' || mutation.type === 'childList') {
                        var newPrice = self._getFinalPrice();
                        self._onPriceChange(newPrice);
                    }
                });
            });

            observer.observe(priceBox[0], {
                childList: true,
                subtree: true,
                characterData: true
            });
        },

        /**
         * Handle price change
         * @param {number} newPrice - The new product price
         * @private
         */
        _onPriceChange: function(newPrice) {
            this.options.productPrice = newPrice;
            this._updatePaymentOptions();
        },

        /**
         * Calculate and update payment options
         * @private
         */
        _updatePaymentOptions: function() {
            var paymentOptions = this.options.paymentOptions;
            var productPrice = this.options.productPrice;

            $('.fineweb-installments').remove();

            for (var key in paymentOptions) {
                if (paymentOptions.hasOwnProperty(key)) {
                    var option = paymentOptions[key];
                    var discountFixed = parseFloat(option.discount_fixed);
                    var discountPercentage = parseFloat(option.discount_percentage);
                    var maxInstallments = parseInt(option.max_installments, 10);
                    var noInterestInstallments = parseInt(option.no_interest_installments, 10);
                    var monthlyInterestRate = parseFloat(option.monthly_interest_rate);

                    var finalPrice = productPrice;

                    // Has fixed discount
                    if (discountFixed > 0) {
                        finalPrice -= discountFixed;
                        // Round the final value to two decimal places
                        finalPrice = finalPrice.toFixed(2);
                    } else if (discountPercentage > 0) {
                        finalPrice -= (productPrice * discountPercentage / 100);
                        // Round the final value to two decimal places
                        finalPrice = finalPrice.toFixed(2);
                    }

                    var content = '<div class="fineweb-installments">';

                    if (finalPrice <= 0) {
                        return;
                    } else if (finalPrice < productPrice) {
                        content += '<p class="fineweb-discount-amount">' + $.mage.__('Discount: ') + this._formatCurrency(productPrice - finalPrice) + '  </p>';
                        content += '<p class="fineweb-final-amount">' + $.mage.__('Final Price: ') + this._formatCurrency(parseFloat(finalPrice)) + '  </p>';
                    }

                    // Installments details
                    if (maxInstallments > 1) {
                        content += '<p>' + $.mage.__('Installment Options:') + '</p>';
                        for (var i = 1; i <= maxInstallments; i++) {
                            if (i <= noInterestInstallments) {
                                content += '<p>' + i + ' x ' + this._formatCurrency(finalPrice / i) + '</p>';
                            } else {
                                var installmentWithInterest = finalPrice * Math.pow(1 + monthlyInterestRate / 100, i) / i;
                                content += '<p>' + i + ' x ' + this._formatCurrency(installmentWithInterest) + ' (' + $.mage.__('with ') + monthlyInterestRate + '% ' + $.mage.__('interest per month') + ')</p>';
                            }
                        }
                    } else {
                        content += '<p>' + this._formatCurrency(finalPrice) + '</p>';
                    }

                    content += '</div>';

                    // Update the HTML of the corresponding LI
                    var $li = $('#payment-option' + key);
                    $li.html('<strong>' + option.payment_method + ':</strong> ' + option.payment_method_content + content);
                }
            }
        },

        /**
         * Get the current final price
         * @private
         * @returns {number}
         */
        _getFinalPrice: function() {
            var finalPriceElement = $('[data-price-type="finalPrice"] .price');
            var priceText = finalPriceElement.text().replace(/[^\d,.]/g, '').replace(',', '.');
            return parseFloat(priceText);
        },

        /**
         * Format currency
         * @private
         * @param {number} amount - The amount to format
         * @returns {string}
         */
        _formatCurrency: function(amount) {
            if (typeof amount === 'number') {
                return new Intl.NumberFormat(undefined, {
                    style: 'currency',
                    currency: $('meta[itemprop="priceCurrency"]').attr('content')
                }).format(amount.toFixed(2));
            } else {
                return '';
            }
        },

        /**
         * Initialize modal
         * @private
         */
        _modalInit: function() {
            var popup = modal(this.modalOptions, $('#paymentOptionsModal'));

            $('#openPaymentOptionsModal').on('click', function() {
                $('#paymentOptionsModal').modal('openModal');
            });
        }
    });

    return $.mage.finewebProductPaymentOptions;
});
