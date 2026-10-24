<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Model;

use Fineweb\ProductPaymentOptions\Model\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var Config
     */
    private Config $config;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->config = new Config($this->scopeConfig);
    }

    public function testReadsPaymentsInTheStoreScopeByDefault(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(Config::XML_PATH_PAYMENTS, ScopeInterface::SCOPE_STORE, null)
            ->willReturn('{"_1":{}}');

        self::assertSame('{"_1":{}}', $this->config->getPayments());
    }

    public function testReadsPaymentsInTheGivenScope(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(Config::XML_PATH_PAYMENTS, ScopeInterface::SCOPE_WEBSITES, '4')
            ->willReturn('[]');

        self::assertSame('[]', $this->config->getPayments(ScopeInterface::SCOPE_WEBSITES, '4'));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function noPaymentsProvider(): array
    {
        return [
            'not configured' => [null],
            'empty string' => [''],
            'not a string' => [['unexpected']],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('noPaymentsProvider')]
    public function testPaymentsAreNullWhenNothingUsableIsSaved(mixed $stored): void
    {
        $this->scopeConfig->method('getValue')->willReturn($stored);

        self::assertNull($this->config->getPayments());
    }

    /**
     * @return array<string, array{mixed, float|null}>
     */
    public static function minInstallmentValueProvider(): array
    {
        return [
            'decimal' => ['12.5', 12.5],
            'integer' => ['100', 100.0],
            'zero' => ['0', 0.0],
            'not configured' => [null, null],
            'empty string' => ['', null],
            'not numeric' => ['abc', null],
        ];
    }

    /**
     * @param mixed $stored
     * @param float|null $expected
     */
    #[DataProvider('minInstallmentValueProvider')]
    public function testMinInstallmentValue(mixed $stored, ?float $expected): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(Config::XML_PATH_MIN_INSTALLMENT_VALUE, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($stored);

        self::assertSame($expected, $this->config->getMinInstallmentValue());
    }

    public function testReadsTheMinInstallmentValueInTheGivenScope(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(Config::XML_PATH_MIN_INSTALLMENT_VALUE, ScopeInterface::SCOPE_STORES, '2')
            ->willReturn('50');

        self::assertSame(50.0, $this->config->getMinInstallmentValue(ScopeInterface::SCOPE_STORES, '2'));
    }
}
