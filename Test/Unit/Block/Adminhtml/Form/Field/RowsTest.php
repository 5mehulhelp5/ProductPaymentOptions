<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Block\Adminhtml\Form\Field;

use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Column;
use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Rows;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\View\LayoutInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionProperty;

class RowsTest extends TestCase
{
    /**
     * @var Column&MockObject
     */
    private Column $renderer;

    /**
     * @var Rows
     */
    private Rows $rows;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(Column::class);
        $this->renderer->method('calcOptionHash')->willReturnCallback(
            static fn (string $value): string => 'hash-' . $value
        );

        $this->rows = $this->getMockBuilder(Rows::class)->disableOriginalConstructor()->onlyMethods([])->getMock();
        (new ReflectionProperty(Rows::class, 'paymentRenderer'))->setValue($this->rows, $this->renderer);
    }

    private function prepareArrayRow(DataObject $row): void
    {
        (new ReflectionMethod(Rows::class, '_prepareArrayRow'))->invoke($this->rows, $row);
    }

    public function testPreselectsTheSavedPaymentMethod(): void
    {
        $row = new DataObject(['payment_method' => 'checkmo']);

        $this->prepareArrayRow($row);

        self::assertSame(['option_hash-checkmo' => 'selected="selected"'], $row->getData('option_extra_attrs'));
    }

    public function testSelectsNothingForARowWithoutPaymentMethod(): void
    {
        $row = new DataObject(['discount_fixed' => '10']);

        $this->prepareArrayRow($row);

        self::assertSame([], $row->getData('option_extra_attrs'));
    }

    /**
     * @param array<int, non-empty-string> $methods
     * @return Rows&MockObject
     */
    private function rowsWithLayout(LayoutInterface $layout, array $methods = []): Rows
    {
        $rows = $this->getMockBuilder(Rows::class)
            ->disableOriginalConstructor()
            ->onlyMethods(array_merge(['getLayout'], $methods))
            ->getMock();
        $rows->method('getLayout')->willReturn($layout);

        return $rows;
    }

    public function testPreparesTheSevenColumnsWithTheDropdownFirst(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($this->renderer);
        $columns = [];
        $rows = $this->rowsWithLayout($layout, ['addColumn']);
        $rows->method('addColumn')->willReturnCallback(
            function (string $name, array $params) use (&$columns): void {
                $columns[$name] = $params;
            }
        );

        (new ReflectionMethod(Rows::class, '_prepareToRender'))->invoke($rows);

        self::assertSame([
            'payment_method',
            'discount_fixed',
            'discount_percentage',
            'max_installments',
            'no_interest_installments',
            'monthly_interest_rate',
            'payment_method_content',
        ], array_keys($columns));
        self::assertSame($this->renderer, $columns['payment_method']['renderer']);
        self::assertSame('required-entry validate-number', $columns['discount_fixed']['class']);
        self::assertSame('required-entry validate-number', $columns['monthly_interest_rate']['class']);
        self::assertArrayNotHasKey('class', $columns['payment_method_content']);
    }

    public function testTheAddButtonDoesNotInsertAfterARow(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->method('createBlock')->willReturn($this->renderer);
        $rows = $this->rowsWithLayout($layout, ['addColumn']);

        (new ReflectionMethod(Rows::class, '_prepareToRender'))->invoke($rows);

        self::assertFalse((new ReflectionProperty(AbstractFieldArray::class, '_addAfter'))->getValue($rows));
        $label = (new ReflectionProperty(AbstractFieldArray::class, '_addButtonLabel'))->getValue($rows);
        self::assertSame('Add', $label);
    }

    public function testCreatesTheRendererBlockOnlyOnce(): void
    {
        $layout = $this->createMock(LayoutInterface::class);
        $layout->expects(self::once())
            ->method('createBlock')
            ->with(Column::class, '', ['data' => ['is_render_to_js_template' => true]])
            ->willReturn($this->renderer);
        $rows = $this->rowsWithLayout($layout);
        $getRenderer = new ReflectionMethod(Rows::class, 'getPaymentRenderer');

        self::assertSame($this->renderer, $getRenderer->invoke($rows));
        self::assertSame($this->renderer, $getRenderer->invoke($rows));
    }
}
