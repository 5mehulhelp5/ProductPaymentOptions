<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Reads the module configuration. Every read passes its scope: the fields are editable per website and store view.
 */
class Config
{
    public const XML_PATH_PAYMENTS = 'product_payment_options/payment_discount/payments';
    public const XML_PATH_MIN_INSTALLMENT_VALUE = 'product_payment_options/payment_discount/min_installment_value';

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(private readonly ScopeConfigInterface $scopeConfig)
    {
    }

    /**
     * Get the serialized payment option rows, or null when none is saved.
     *
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string|null
     */
    public function getPayments(string $scopeType = ScopeInterface::SCOPE_STORE, ?string $scopeCode = null): ?string
    {
        $payments = $this->scopeConfig->getValue(self::XML_PATH_PAYMENTS, $scopeType, $scopeCode);

        return is_string($payments) && $payments !== '' ? $payments : null;
    }

    /**
     * Get the minimum installment value, or null when it is not configured.
     *
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return float|null
     */
    public function getMinInstallmentValue(
        string $scopeType = ScopeInterface::SCOPE_STORE,
        ?string $scopeCode = null
    ): ?float {
        $value = $this->scopeConfig->getValue(self::XML_PATH_MIN_INSTALLMENT_VALUE, $scopeType, $scopeCode);

        return is_numeric($value) ? (float) $value : null;
    }
}
