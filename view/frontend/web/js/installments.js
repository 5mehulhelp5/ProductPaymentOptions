/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */
define([], function () {
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
     * Round a monetary amount to two decimal places. Only used for what is displayed: the calculation itself is
     * never rounded.
     *
     * @param {Number} amount
     * @returns {Number}
     */
    function roundMoney(amount) {
        return Math.round((amount + Number.EPSILON) * 100) / 100;
    }

    /**
     * Installment of a fixed-payment (Tabela Price) loan: principal × i / (1 − (1 + i)^−n),
     * with i the monthly rate. Without interest it is principal / n.
     *
     * @param {Number} principal
     * @param {Number} count - Number of installments
     * @param {Number} monthlyRate - Monthly interest in percent, e.g. 1.99
     * @returns {Number}
     */
    function priceTablePayment(principal, count, monthlyRate) {
        const rate = monthlyRate / 100;

        return rate > 0 ? principal * rate / (1 - Math.pow(1 + rate, -count)) : principal / count;
    }

    /**
     * Price after the discount of a payment option: a fixed discount takes precedence over a percentage one.
     *
     * @param {Number} price
     * @param {Object} option
     * @returns {Number}
     */
    function discountedPrice(price, option) {
        const fixed = toNumber(option.discount_fixed);
        const percentage = toNumber(option.discount_percentage);
        let final = price;

        if (fixed > 0) {
            final -= fixed;
        } else if (percentage > 0) {
            final -= price * percentage / 100;
        }

        return Math.max(final, 0);
    }

    /**
     * Everything the modal shows for one payment option. Amounts are not rounded.
     *
     * @param {Number} price - Product price
     * @param {Object} option - One configured row
     * @param {Number} minInstallmentValue - Installments below this amount are left out
     * @returns {{final: Number, discount: Number, hasDiscount: Boolean, monthlyRate: Number, installments: Array}}
     */
    function calculate(price, option, minInstallmentValue) {
        const final = discountedPrice(price, option);
        const maxInstallments = parseInt(option.max_installments, 10);
        const noInterestInstallments = parseInt(option.no_interest_installments, 10) || 0;
        const monthlyRate = toNumber(option.monthly_interest_rate);
        const installments = [];

        if (maxInstallments > 1) {
            for (let count = 1; count <= maxInstallments; count++) {
                const withInterest = count > noInterestInstallments;
                const amount = priceTablePayment(final, count, withInterest ? monthlyRate : 0);

                if (amount >= minInstallmentValue) {
                    installments.push({count: count, amount: amount, withInterest: withInterest});
                }
            }
        }

        return {
            final: final,
            discount: price - final,
            hasDiscount: toNumber(option.discount_fixed) > 0 || toNumber(option.discount_percentage) > 0,
            monthlyRate: monthlyRate,
            installments: installments
        };
    }

    return {
        toNumber: toNumber,
        roundMoney: roundMoney,
        priceTablePayment: priceTablePayment,
        calculate: calculate
    };
});
