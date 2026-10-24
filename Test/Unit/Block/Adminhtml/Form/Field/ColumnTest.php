<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Block\Adminhtml\Form\Field;

use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Column;
use PHPUnit\Framework\TestCase;

class ColumnTest extends TestCase
{
    public function testSetInputNameAndIdAreFluentAndStoreTheValues(): void
    {
        $column = $this->getMockBuilder(Column::class)->disableOriginalConstructor()->onlyMethods([])->getMock();

        self::assertSame($column, $column->setInputName('groups[payments][value][<%- _id %>][payment_method]'));
        self::assertSame($column, $column->setInputId('payment_method_<%- _id %>'));
        self::assertSame('groups[payments][value][<%- _id %>][payment_method]', $column->getData('name'));
        self::assertSame('payment_method_<%- _id %>', $column->getData('id'));
    }
}
