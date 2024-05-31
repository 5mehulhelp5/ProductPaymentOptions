<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Block;

//use Elgin\PaymentMultiple\Block\Adminhtml\Form\Field\PaymentMethodsColumn;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Discount
 *
 * Class to render component
 *
 * @author      Webjump Develop Team <dev@webjump.com.br>
 * @author      Luis Vicente <luis.vicente@webjump.com.br>
 * @copyright   2023 Webjump (http://www.webjump.com.br)
 * @license     http://www.webjump.com.br Copyright
 * @link        http://www.webjump.com.br
 */
class PaymentOptions extends AbstractFieldArray
{

    //private $paymentMethodsColumn;

    /** @var YesNoColumn */
    private $yesNoColumn;

    /**
     * Prepare rendering the new field by adding all the needed columns
     *
     * @throws LocalizedException
     */
    protected function _prepareToRender()
    {
        $this->addColumn('payment', [
            'label' => __('Payment Method'),
            'renderer' => $this->getPaymentRenderer(),
            'class' => 'required-entry'
        ]);
        $this->addColumn('percentage', [
            'label' => __('Discount %'),
            'class' => 'required-entry validate-digits-range digits-range-0-100'
        ]);
        $this->addColumn('default', [
            'label' => __('Is Default'),
            'renderer' => $this->getYesNoRenderer(),
            'class' => 'required-entry'
        ]);
        $this->addColumn('installments', [
            'label' => __('Has Installments'),
            'renderer' => $this->getYesNoRenderer(),
            'class' => 'required-entry'
        ]);
        $this->addColumn('label', [
            'label' => __('Content'),
            'class' => 'required-entry',
            'style' => 'margin-right: 226px;'
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

//    /**
//     * Render column payment method
//     *
//     * @return PaymentMethodsColumn
//     * @throws LocalizedException
//     */
//    private function getPaymentRenderer(): PaymentMethodsColumn
//    {
//        if (!$this->paymentMethodsColumn) {
//            $this->paymentMethodsColumn = $this->getLayout()->createBlock(
//                PaymentMethodsColumn::class,
//                '',
//                ['data' => ['is_render_to_js_template' => true]]
//            );
//        }
//        return $this->paymentMethodsColumn;
//    }

//    /**
//     * Render column yes/no
//     *
//     * @return YesNoColumn
//     * @throws LocalizedException
//     */
//    private function getYesNoRenderer(): YesNoColumn
//    {
//        if (!$this->yesNoColumn) {
//            $this->yesNoColumn = $this->getLayout()->createBlock(
//                YesNoColumn::class,
//                '',
//                ['data' => ['is_render_to_js_template' => true]]
//            );
//        }
//        return $this->yesNoColumn;
//    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $paymentMethod = $row->getPayment();
        if ($paymentMethod !== null) {
            $options['option_' . $this->getPaymentRenderer()->calcOptionHash($paymentMethod)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
