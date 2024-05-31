<?php

namespace Fineweb\ProductPaymentOptions\Model;

use Magento\Framework\Model\AbstractModel;

class ProductPaymentOptions extends AbstractModel
{
    protected function _construct()
    {
        $this->_init('Fineweb\ProductPaymentOptions\Model\ResourceModel\ProductPaymentOptions');
    }
}
