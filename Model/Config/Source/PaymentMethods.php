<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * Provides the active payment methods as options for configuration fields.
 */
class PaymentMethods implements OptionSourceInterface
{
    /**
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(private readonly PaymentConfig $paymentConfig)
    {
    }

    /**
     * Retrieve the active payment methods as an array of value/label options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        $options = [];
        foreach ($this->paymentConfig->getActiveMethods() as $method) {
            $options[] = [
                'value' => (string) $method->getCode(),
                'label' => (string) $method->getTitle(),
            ];
        }

        return $options;
    }
}
