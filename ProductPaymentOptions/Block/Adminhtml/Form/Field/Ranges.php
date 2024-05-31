<?php
namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\TaxColumn;

/**
 * Class Ranges
 */
class Ranges extends AbstractFieldArray
{
    /**
     * @var TaxColumn
     */
    private $taxRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getTaxRenderer()
        ]);
        $this->addColumn('discount_fixed', [
            'label' => __('Fixed Discount'),
            'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('discount_percentage', [
            'label' => __('Percentage Discount'),
            'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('max_installments', [
            'label' => __('Max Installments Allowed'),
            'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('no_interest_installments', [
            'label' => __('Number of No Interest Installments'),
            'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('monthly_interest_rate', [
            'label' => __('Monthly Interest Rate (%) - Ex.: 1.4'),
            'class' => 'required-entry validate-number'
        ]);
        $this->addColumn('payment_method_content', [
            'label' => __('Payment Method Content to Show'),
            'class' => 'required-entry'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $tax = $row->getTax();
        if ($tax !== null) {
            $options['option_' . $this->getTaxRenderer()->calcOptionHash($tax)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * @return TaxColumn
     * @throws LocalizedException
     */
    private function getTaxRenderer()
    {
        if (!$this->taxRenderer) {
            $this->taxRenderer = $this->getLayout()->createBlock(
                TaxColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->taxRenderer;
    }
}
