<?php
declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;


use Magento\Framework\View\Element\Html\Select;
use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;

class TaxColumn extends Select
{

    protected $finewebPaymentOptions;

    public function __construct(
        PaymentMethods $finewebPaymentOptions,
        \Magento\Framework\View\Element\Context $context,
        array $data = []
    ) {
        $this->finewebPaymentOptions = $finewebPaymentOptions;
        parent::__construct($context, $data);
    }

    /**
     * Set "name" for <select> element
     *
     * @param string $value
     * @return $this
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element
     *
     * @param $value
     * @return $this
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }
        return parent::_toHtml();
    }

    private function getSourceOptions(): array
    {
        return $this->finewebPaymentOptions->toOptionArray();
    }
}
