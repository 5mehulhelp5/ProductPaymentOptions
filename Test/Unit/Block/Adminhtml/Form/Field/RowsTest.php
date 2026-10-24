<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Block\Adminhtml\Form\Field;

use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Column;
use Fineweb\ProductPaymentOptions\Block\Adminhtml\Form\Field\Rows;
use Magento\Framework\DataObject;
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
}
