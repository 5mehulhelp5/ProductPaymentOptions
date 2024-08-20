<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;
use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;

/**
 * Class Column
 *  This class represents a custom HTML select element used in admin forms
 *  to display payment method options fetched from the system configuration.
 */
class Column extends Select
{

    /**
     * @var PaymentMethods
     */
    protected PaymentMethods $finewebPaymentOptions;

    /**
     * Column constructor.
     *
     * @param PaymentMethods $finewebPaymentOptions
     * @param Context        $context
     * @param array          $data
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
     *
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
     *
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
    public function toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::toHtml();
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
