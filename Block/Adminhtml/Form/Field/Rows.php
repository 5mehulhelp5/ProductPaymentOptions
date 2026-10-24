<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin field array that manages one row of discount and installment rules per payment method.
 */
class Rows extends AbstractFieldArray
{
    private const VALIDATION_CLASS = 'required-entry validate-number';

    /**
     * @var Column|null
     */
    private ?Column $paymentRenderer = null;

    /**
     * Prepare rendering the new field by adding all the needed columns
     *
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('payment_method', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentRenderer(),
        ]);
        $this->addColumn('discount_fixed', [
            'label' => __('Fixed Discount'),
            'class' => self::VALIDATION_CLASS,
        ]);
        $this->addColumn('discount_percentage', [
            'label' => __('Percentage Discount'),
            'class' => self::VALIDATION_CLASS,
        ]);
        $this->addColumn('max_installments', [
            'label' => __('Max Installments Allowed'),
            'class' => self::VALIDATION_CLASS,
        ]);
        $this->addColumn('no_interest_installments', [
            'label' => __('Number of No Interest Installments'),
            'class' => self::VALIDATION_CLASS,
        ]);
        $this->addColumn('monthly_interest_rate', [
            'label' => __('Monthly Interest Rate (%) - Ex.: 0.9'),
            'class' => self::VALIDATION_CLASS,
        ]);
        $this->addColumn('payment_method_content', [
            'label' => __('Payment Method Content to Show'),
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = (string) __('Add');
    }

    /**
     * Pre-select the saved payment method in the row's dropdown
     *
     * @param DataObject $row
     * @return void
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $paymentMethod = $row->getData('payment_method');
        if (is_string($paymentMethod)) {
            $hash = $this->getPaymentRenderer()->calcOptionHash($paymentMethod);
            $options['option_' . $hash] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Get the payment method dropdown renderer
     *
     * @return Column
     * @throws LocalizedException
     */
    private function getPaymentRenderer(): Column
    {
        if ($this->paymentRenderer === null) {
            $this->paymentRenderer = $this->getLayout()->createBlock(
                Column::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->paymentRenderer;
    }
}
