<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 * @author      Roni Clei Santos
 */

namespace Fineweb\ProductPaymentOptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * Class PaymentMethods
 * @package Fineweb\ProductPaymentOptions\Model\Config\Source
 */
class PaymentMethods implements ArrayInterface
{
    /**
     * @var PaymentConfig
     */
    protected PaymentConfig $paymentConfig;

    /**
     * PaymentMethods constructor.
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(PaymentConfig $paymentConfig)
    {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Retrieve available payment methods
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = [];
        $methods = $this->paymentConfig->getActiveMethods();
        foreach ($methods as $method) {
            $options[] = [
                'value' => $method->getCode(),
                'label' => $method->getTitle(),
            ];
        }
        return $options;
    }
}
