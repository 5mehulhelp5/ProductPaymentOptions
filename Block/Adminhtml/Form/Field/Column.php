<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Select element used in the admin field array to pick one of the active payment methods.
 */
class Column extends Select
{
    /**
     * @param PaymentMethods $paymentMethods
     * @param Context $context
     * @param mixed[] $data
     */
    public function __construct(
        private readonly PaymentMethods $paymentMethods,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Set input name
     *
     * @param string $value
     * @return $this
     */
    public function setInputName(string $value): static
    {
        return $this->setData('name', $value);
    }

    /**
     * Set input ID
     *
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value): static
    {
        return $this->setData('id', $value);
    }

    /**
     * Fill the select with the active payment methods when no options were set explicitly
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->paymentMethods->toOptionArray());
        }

        return parent::_toHtml();
    }
}
