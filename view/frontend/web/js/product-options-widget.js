define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'jquery/ui'
], function ($, modal) {
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
         * Observe changes in the final price
         * @private
         */
        _observePriceChanges: function () {
            const self = this;
            const priceBox = $('[data-role="priceBox"]');

            if (priceBox.length) {
                priceBox.each(function () {
                    self._startObservingPriceBox($(this));
                });
            }

            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        $(mutation.addedNodes).each(function () {
                            const newPriceBoxes = $(this).find('[data-role="priceBox"]');
                            if (newPriceBoxes.length) {
                                newPriceBoxes.each(function () {
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
        _startObservingPriceBox: function (priceBox) {
            const self = this;
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'characterData' || mutation.type === 'childList') {
                        const newPrice = self._getFinalPrice();
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
            const minInstallmentValue = this.options.minInstallmentValue;

            $('.fineweb-installments').remove();

            for (const key in paymentOptions) {
                if (paymentOptions.hasOwnProperty(key)) {
                    const option = paymentOptions[key];
                    const discountFixed = parseFloat(option.discount_fixed);
                    const discountPercentage = parseFloat(option.discount_percentage);
                    const maxInstallments = parseInt(option.max_installments, 10);
                    const noInterestInstallments = parseInt(option.no_interest_installments, 10);
                    const monthlyInterestRate = parseFloat(option.monthly_interest_rate);
                    let content = '<div class="fineweb-installments">';
                    let finalPrice = productPrice;

                    // Apply fixed or percentage discount
                    if (discountFixed > 0) {
                        finalPrice -= discountFixed;
                    } else if (discountPercentage > 0) {
                        finalPrice -= (productPrice * discountPercentage / 100);
                    }

                    // Ensure finalPrice is not less than zero
                    finalPrice = Math.max(finalPrice, 0);

                    // Round finalPrice for display purposes
                    finalPrice = parseFloat(finalPrice.toFixed(1));

                    // Add discount if applicable
                    if (discountFixed > 0 || discountPercentage > 0) {
                        content += '<p class="fineweb-discount-amount">' + $.mage.__('Discount: ') + this._formatCurrency(productPrice - finalPrice) + '</p>';
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
                                // Calculate installment with interest
                                const interestMultiplier = Math.pow(1 + monthlyInterestRate / 100, i);
                                installmentAmount = (finalPrice * interestMultiplier) / i;
                            }

                            // Round installmentAmount for display purposes
                            installmentAmount = parseFloat(installmentAmount.toFixed(1));

                            // Check if the installment amount is below the minimum value
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
         * Get the current final price
         * @private
         * @returns {number}
         */
        _getFinalPrice: function () {
            const finalPriceElement = $('[data-price-type="finalPrice"] .price');
            const priceText = finalPriceElement.text().replace(/[^\d,.]/g, '').replace(',', '.');
            return parseFloat(priceText);
        },

        /**
         * Format currency
         * @private
         * @param {number} amount - The amount to format
         * @returns {string}
         */
        _formatCurrency: function (amount) {
            if (typeof amount === 'number') {
                return new Intl.NumberFormat(undefined, {
                    style: 'currency',
                    currency: $('meta[itemprop="priceCurrency"]').attr('content')
                }).format(amount.toFixed(1));
            } else {
                return '';
            }
        },

        /**
         * Initialize modal
         * @private
         */
        _modalInit: function () {
            const popup = modal(this.modalOptions, $('#paymentOptionsModal'));

            $('#openPaymentOptionsModal').on('click', function () {
                $('#paymentOptionsModal').modal('openModal');
            });
        }
    });

    return $.mage.finewebProductPaymentOptions;
});
