<?php

namespace Fineweb\ProductPaymentOptions\Model\ResourceModel\ProductPaymentOptions;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Fineweb\ProductPaymentOptions\Model\ProductPaymentOptions', 'Fineweb\ProductPaymentOptions\Model\ResourceModel\ProductPaymentOptions');
    }
}
