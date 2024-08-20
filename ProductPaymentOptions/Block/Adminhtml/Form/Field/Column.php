<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 * @author      Roni Clei Santos
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;

/**
 * Class Column
 * @package Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field
 * @method setName(string $value)
 */
class Column extends Select
{

    /**
     * @var PaymentMethods
     */
    protected PaymentMethods $finewebPaymentOptions;

    /**
     * Column constructor.
     * @param PaymentMethods $finewebPaymentOptions
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        PaymentMethods $finewebPaymentOptions,
        Context $context,
        array $data = []
    ) {
        $this->finewebPaymentOptions = $finewebPaymentOptions;
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
        return $this->setName($value);
    }

    /**
     * Set input ID
     *
     * @param string $value
     * @return $this
     */
    public function setInputId(string $value): static
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    protected function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    /**
     * Get source options
     *
     * @return array
     */
    private function getSourceOptions(): array
    {
        return $this->finewebPaymentOptions->toOptionArray();
    }
}
