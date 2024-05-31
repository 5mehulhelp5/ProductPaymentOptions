<?php

namespace Fineweb\ProductPaymentOptions\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ProductPaymentOptions extends Template
{
    protected $scopeConfig;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    public function getProductPaymentOptions()
    {
        return json_decode($this->scopeConfig->getValue('product_payment_options/payment_discount/ranges'), true);
    }
}
