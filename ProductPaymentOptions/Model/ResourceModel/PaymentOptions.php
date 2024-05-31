<?php

namespace Fineweb\ProductPaymentOptions\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductPaymentOptions extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('payment_options', 'id');
    }
}
