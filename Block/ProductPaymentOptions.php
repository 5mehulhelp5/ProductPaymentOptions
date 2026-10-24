<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block;

use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Store\Model\ScopeInterface;

/**
 * Block class for retrieving and formatting product payment options.
 */
class ProductPaymentOptions extends Template
{
    public const XML_PATH_PAYMENTS = 'product_payment_options/payment_discount/payments';
    public const XML_PATH_MIN_INSTALLMENT_VALUE = 'product_payment_options/payment_discount/min_installment_value';

    /**
     * Keys every configured row exposes to the template and the widget, with their fallback values.
     */
    private const ROW_DEFAULTS = [
        'payment_method' => '',
        'discount_fixed' => '',
        'discount_percentage' => '',
        'max_installments' => '',
        'no_interest_installments' => '',
        'monthly_interest_rate' => '',
        'payment_method_content' => '',
    ];

    /**
     * @param Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param PaymentConfig $paymentConfig
     * @param Json $json
     * @param JsonHexTag $jsonHexTag
     * @param mixed[] $data
     */
    public function __construct(
        Template\Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly PaymentConfig $paymentConfig,
        private readonly Json $json,
        private readonly JsonHexTag $jsonHexTag,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve the configured payment option rows, with each payment method code resolved to its title.
     *
     * @return array<string, array<string, string>>|null
     */
    public function getProductPaymentOptions(): ?array
    {
        $payments = $this->scopeConfig->getValue(self::XML_PATH_PAYMENTS, ScopeInterface::SCOPE_STORE);
        if (!is_string($payments) || $payments === '') {
            return null;
        }

        try {
            $rows = $this->json->unserialize($payments);
        } catch (InvalidArgumentException) {
            return null;
        }
        if (!is_array($rows)) {
            return null;
        }

        $titles = $this->getActivePaymentMethodTitles();
        $options = [];
        foreach ($rows as $key => $row) {
            if (!is_array($row)) {
                continue;
            }
            $row += self::ROW_DEFAULTS;
            $row['payment_method'] = $titles[$row['payment_method']] ?? $row['payment_method'];
            $options[$key] = $row;
        }

        return $options;
    }

    /**
     * Retrieve the minimum installment value, or null when it is not configured.
     *
     * @return float|null
     */
    public function getMinInstallmentValue(): ?float
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_MIN_INSTALLMENT_VALUE, ScopeInterface::SCOPE_STORE);

        return is_numeric($value) ? (float) $value : null;
    }

    /**
     * Serialize data for the `text/x-magento-init` script, safe to print inside a `<script>` tag.
     *
     * @param mixed[] $data
     * @return string
     */
    public function serializeForScript(array $data): string
    {
        return (string) $this->jsonHexTag->serialize($data);
    }

    /**
     * Map active payment method codes to their titles.
     *
     * @return array<string, string>
     */
    private function getActivePaymentMethodTitles(): array
    {
        $titles = [];
        foreach ($this->paymentConfig->getActiveMethods() as $code => $method) {
            $title = (string) $method->getTitle();
            if ($title !== '') {
                $titles[(string) $code] = $title;
            }
        }

        return $titles;
    }
}
