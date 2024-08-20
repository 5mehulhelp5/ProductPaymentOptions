<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 */

namespace Fineweb\ProductPaymentOptions\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Config;

/**
 * Block class for retrieving and formatting product payment options.
 */
class ProductPaymentOptions extends Template
{
    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    protected Config $paymentConfig;

    /**
     * ProductPaymentOptions constructor.
     * @param Template\Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Config $paymentConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        Config $paymentConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->paymentConfig = $paymentConfig;
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
            $paymentMethods = json_decode($payments, true);
            foreach ($paymentMethods as &$paymentMethod) {
                $methodCode = $paymentMethod['payment_method'] ?? null;
                if ($methodCode) {
                    $methodTitle = $this->getPaymentMethodTitle($methodCode);
                    if ($methodTitle) {
                        $paymentMethod['payment_method'] = $methodTitle;
                    }
                }
            }
            return $paymentMethods;
        }
        return null;
    }

    /**
     * Get payment method title by code
     *
     * @param string $methodCode
     * @return string|null
     */
    protected function getPaymentMethodTitle(string $methodCode): ?string
    {
        $methods = $this->paymentConfig->getActiveMethods();
        if (isset($methods[$methodCode])) {
            return $methods[$methodCode]->getTitle();
        }
        return null;
    }

    /**
     * Retrieve minimum installment value
     *
     * @return float|null
     */
    public function getMinInstallmentValue(): ?float
    {
        return $this->scopeConfig->getValue('product_payment_options/payment_discount/min_installment_value');
    }
}
