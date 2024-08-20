<?php
/**
 * Fineweb_ProductPaymentOptions extension
 *
 * @category    Fineweb
 * @package     Fineweb_ProductPaymentOptions
 * @copyright   Copyright © Fineweb, Inc. All rights reserved.
 * @author      Roni Clei Santos
 */

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Rows
 * @package Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field
 */
class Rows extends AbstractFieldArray
{
    /**
     * @var string
     */
    const VALIDATION_CLASS = 'required-entry validate-number';

    /**
     * @var Column
     */
    private Column $paymentRenderer;

    /**
     * Rows constructor.
     * @param Context $context
     * @param array $data
     * @throws LocalizedException
     */
    public function __construct(
        Context $context,
        array   $data = []
    ) {
        parent::__construct($context, $data);
        $this->paymentRenderer = $this->getLayout()->createBlock(
            Column::class,
            '',
            ['data' => ['is_render_to_js_template' => true]]
        );
    }

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentRenderer()
        ]);
        $this->addColumn('discount_fixed', [
            'label' => __('Fixed Discount'),
            'class' => self::VALIDATION_CLASS
        ]);
        $this->addColumn('discount_percentage', [
            'label' => __('Percentage Discount'),
            'class' => self::VALIDATION_CLASS
        ]);
        $this->addColumn('max_installments', [
            'label' => __('Max Installments Allowed'),
            'class' => self::VALIDATION_CLASS
        ]);
        $this->addColumn('no_interest_installments', [
            'label' => __('Number of No Interest Installments'),
            'class' => self::VALIDATION_CLASS
        ]);
        $this->addColumn('monthly_interest_rate', [
            'label' => __('Monthly Interest Rate (%) - Ex.: 0.9'),
            'class' => self::VALIDATION_CLASS
        ]);
        $this->addColumn('payment_method_content', [
            'label' => __('Payment Method Content to Show'),
            'class' => self::VALIDATION_CLASS
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

        $payment = $row->getPayment();
        if ($payment !== null) {
            $options['option_' . $this->getPaymentRenderer()->calcOptionHash($payment)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get payment renderer block
     *
     * @return Column
     */
    private function getPaymentRenderer(): Column
    {
        return $this->paymentRenderer;
    }
}
