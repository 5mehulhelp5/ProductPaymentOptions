<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\ViewModel;

use Fineweb\ProductPaymentOptions\Model\Config;
use InvalidArgumentException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Data of the payment options shown on the product page.
 */
class PaymentOptions implements ArgumentInterface
{
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
     * @param Config $config
     * @param PaymentMethodListInterface $paymentMethodList
     * @param StoreManagerInterface $storeManager
     * @param Json $json
     * @param JsonHexTag $jsonHexTag
     */
    public function __construct(
        private readonly Config $config,
        private readonly PaymentMethodListInterface $paymentMethodList,
        private readonly StoreManagerInterface $storeManager,
        private readonly Json $json,
        private readonly JsonHexTag $jsonHexTag
    ) {
    }

    /**
     * Retrieve the payment option rows of the payment methods active in the current store.
     *
     * Each payment method code is resolved to its title; rows of methods that the store does not offer are left out.
     *
     * @return array<string, array<string, string>>|null
     */
    public function getOptions(): ?array
    {
        $payments = $this->config->getPayments();
        if ($payments === null) {
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
            $row = is_array($row) ? $row + self::ROW_DEFAULTS : null;
            if ($row !== null && is_string($row['payment_method']) && isset($titles[$row['payment_method']])) {
                $row['payment_method'] = $titles[$row['payment_method']];
                $options[$key] = $row;
            }
        }

        return $options;
    }

    /**
     * Retrieve the minimum installment value (0 when it is not configured).
     *
     * @return float
     */
    public function getMinInstallmentValue(): float
    {
        return $this->config->getMinInstallmentValue() ?? 0.0;
    }

    /**
     * Serialize the widget configuration for the `text/x-magento-init` script, safe inside a `<script>` tag.
     *
     * @return string
     */
    public function getInitConfig(): string
    {
        return (string) $this->jsonHexTag->serialize([
            '[data-role="fineweb-payment-options"]' => [
                'finewebProductPaymentOptions' => [
                    'paymentOptions' => $this->getOptions() ?? [],
                    'minInstallmentValue' => $this->getMinInstallmentValue(),
                ],
            ],
        ]);
    }

    /**
     * Map the codes of the payment methods active in the current store to their titles.
     *
     * @return array<string, string>
     */
    private function getActivePaymentMethodTitles(): array
    {
        $titles = [];
        foreach ($this->paymentMethodList->getActiveList((int) $this->storeManager->getStore()->getId()) as $method) {
            $code = (string) $method->getCode();
            $title = (string) $method->getTitle();
            $titles[$code] = $title !== '' ? $title : $code;
        }

        return $titles;
    }
}
