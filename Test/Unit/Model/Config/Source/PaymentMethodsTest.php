<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Model\Config\Source;

use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Payment\Model\MethodInterface;
use PHPUnit\Framework\TestCase;

class PaymentMethodsTest extends TestCase
{
    public function testListsActivePaymentMethodsAsValueLabelPairs(): void
    {
        $checkmo = $this->createMock(MethodInterface::class);
        $checkmo->method('getCode')->willReturn('checkmo');
        $checkmo->method('getTitle')->willReturn('Check / Money order');
        $bankTransfer = $this->createMock(MethodInterface::class);
        $bankTransfer->method('getCode')->willReturn('banktransfer');
        $bankTransfer->method('getTitle')->willReturn('Bank Transfer Payment');
        $paymentConfig = $this->createMock(PaymentConfig::class);
        $paymentConfig->method('getActiveMethods')->willReturn([
            'checkmo' => $checkmo,
            'banktransfer' => $bankTransfer,
        ]);

        $options = (new PaymentMethods($paymentConfig))->toOptionArray();

        self::assertSame([
            ['value' => 'checkmo', 'label' => 'Check / Money order'],
            ['value' => 'banktransfer', 'label' => 'Bank Transfer Payment'],
        ], $options);
    }

    public function testReturnsAnEmptyListWhenNoMethodIsActive(): void
    {
        $paymentConfig = $this->createMock(PaymentConfig::class);
        $paymentConfig->method('getActiveMethods')->willReturn([]);

        self::assertSame([], (new PaymentMethods($paymentConfig))->toOptionArray());
    }
}
