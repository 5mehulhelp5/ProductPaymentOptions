<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @copyright   Copyright © Fineweb, Inc. (https://fineweb.com.br/)
 * @author      Roni Clei Santos
 */

namespace Fineweb\ProductPaymentOptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config as PaymentConfig;

/**
 * Provides payment methods as options for configuration.
 */
class PaymentMethods implements ArrayInterface
{
    /**
     * @var PaymentConfig
     */
    protected $paymentConfig;

    /**
     * PaymentMethods constructor.
     * @param PaymentConfig $paymentConfig
     */
    public function __construct(PaymentConfig $paymentConfig)
    {
        $this->paymentConfig = $paymentConfig;
    }

    /**
     * Retrieve available payment methods as an array of options.
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
