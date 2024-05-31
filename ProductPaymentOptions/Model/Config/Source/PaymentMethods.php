<?php
namespace Fineweb\ProductPaymentOptions\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Payment\Model\Config as PaymentConfig;

class PaymentMethods implements ArrayInterface
{
    protected $paymentConfig;

    public function __construct(PaymentConfig $paymentConfig)
    {
        $this->paymentConfig = $paymentConfig;
    }

    public function toOptionArray()
    {
        $options = [];
        $methods = $this->paymentConfig->getActiveMethods();
        foreach ($methods as $method) {
            $options[] = [
                'value' => $method->getTitle(),
                'label' => $method->getTitle(),
            ];
        }
        return $options;
    }
}
