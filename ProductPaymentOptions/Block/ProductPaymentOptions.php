<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 * @author      Roni Clei Santos
 */

namespace Fineweb\ProductPaymentOptions\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class ProductPaymentOptions
 * @package Fineweb\ProductPaymentOptions\Block
 */
class ProductPaymentOptions extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * ProductPaymentOptions constructor.
     * @param Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve product payment options
     *
     * @return array|null
     */
    public function getProductPaymentOptions(): ?array
    {
        $payments = $this->scopeConfig->getValue('product_payment_options/payment_discount/payments');
        if ($payments !== null) {
            return json_decode($payments, true);
        }
        return null;
    }
}
