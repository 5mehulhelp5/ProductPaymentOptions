<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Block\Adminhtml\Form\Field;

use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Column;
use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Context;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

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

    /**
     * @param PaymentMethods $source
     * @return Column
     */
    private function columnWith(PaymentMethods $source): Column
    {
        $context = $this->createMock(Context::class);
        $context->method('getEscaper')->willReturn(new Escaper());

        return new Column($source, $context);
    }

    public function testFillsTheOptionsFromThePaymentMethodsWhenNoneWereSet(): void
    {
        $options = [['value' => 'checkmo', 'label' => 'Check / Money order']];
        $source = $this->createMock(PaymentMethods::class);
        $source->expects(self::once())->method('toOptionArray')->willReturn($options);
        $column = $this->columnWith($source);

        $html = (new ReflectionMethod(Column::class, '_toHtml'))->invoke($column);

        self::assertSame($options, $column->getOptions());
        self::assertIsString($html);
        self::assertStringContainsString('<select', $html);
        self::assertStringContainsString('Check / Money order', $html);
    }

    public function testKeepsTheOptionsThatWereSetExplicitly(): void
    {
        $source = $this->createMock(PaymentMethods::class);
        $source->expects(self::never())->method('toOptionArray');
        $column = $this->columnWith($source);
        $column->setOptions([['value' => 'free', 'label' => 'No Payment Information Required']]);

        $html = (new ReflectionMethod(Column::class, '_toHtml'))->invoke($column);

        self::assertIsString($html);
        self::assertStringContainsString('No Payment Information Required', $html);
    }
}
