<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Block;

use Fineweb\ProductPaymentOptions\Block\ProductPaymentOptions;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPaymentOptionsTest extends TestCase
{
    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var PaymentConfig&MockObject
     */
    private PaymentConfig $paymentConfig;

    /**
     * @var ProductPaymentOptions
     */
    private ProductPaymentOptions $block;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->paymentConfig = $this->createMock(PaymentConfig::class);
        $json = new Json();

        $this->block = new ProductPaymentOptions(
            $this->createMock(Context::class),
            $this->scopeConfig,
            $this->paymentConfig,
            $json,
            new JsonHexTag()
        );
    }

    /**
     * @param string $title
     * @return MethodInterface&MockObject
     */
    private function paymentMethod(string $title): MethodInterface
    {
        $method = $this->createMock(MethodInterface::class);
        $method->method('getTitle')->willReturn($title);

        return $method;
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function emptyConfigProvider(): array
    {
        return [
            'not configured' => [null],
            'empty string' => [''],
            'not a string' => [['unexpected']],
            'invalid json' => ['{not json'],
            'scalar json' => ['42'],
        ];
    }

    /**
     * @param mixed $stored
     */
    #[DataProvider('emptyConfigProvider')]
    public function testReturnsNullWhenPaymentsAreNotUsable(mixed $stored): void
    {
        $this->scopeConfig->method('getValue')->willReturn($stored);

        self::assertNull($this->block->getProductPaymentOptions());
    }

    public function testReadsPaymentsFromTheStoreScope(): void
    {
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(ProductPaymentOptions::XML_PATH_PAYMENTS, ScopeInterface::SCOPE_STORE)
            ->willReturn(null);

        $this->block->getProductPaymentOptions();
    }

    public function testResolvesPaymentMethodCodesToTitles(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'checkmo', 'discount_percentage' => '5', 'max_installments' => '1'],
            '_2' => ['payment_method' => 'banktransfer', 'max_installments' => '6'],
        ]));
        $this->paymentConfig->method('getActiveMethods')->willReturn([
            'checkmo' => $this->paymentMethod('Check / Money order'),
            'banktransfer' => $this->paymentMethod('Bank Transfer Payment'),
        ]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame(['_1', '_2'], array_keys($options));
        self::assertSame('Check / Money order', $options['_1']['payment_method']);
        self::assertSame('Bank Transfer Payment', $options['_2']['payment_method']);
        self::assertSame('5', $options['_1']['discount_percentage']);
    }

    public function testKeepsTheCodeWhenTheMethodIsNotActive(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'disabled_method'],
        ]));
        $this->paymentConfig->method('getActiveMethods')->willReturn([
            'checkmo' => $this->paymentMethod('Check / Money order'),
        ]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame('disabled_method', $options['_1']['payment_method']);
    }

    public function testKeepsTheCodeWhenTheActiveMethodHasNoTitle(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'untitled'],
        ]));
        $this->paymentConfig->method('getActiveMethods')->willReturn(['untitled' => $this->paymentMethod('')]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame('untitled', $options['_1']['payment_method']);
    }

    public function testFillsMissingRowKeysSoTheTemplateNeverReadsAnUndefinedIndex(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'checkmo'],
        ]));
        $this->paymentConfig->method('getActiveMethods')->willReturn([]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame('', $options['_1']['payment_method_content']);
        self::assertSame('', $options['_1']['monthly_interest_rate']);
    }

    public function testSkipsRowsThatAreNotArrays(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => 'garbage',
            '_2' => ['payment_method' => 'checkmo'],
        ]));
        $this->paymentConfig->method('getActiveMethods')->willReturn([]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame(['_2'], array_keys($options));
    }

    public function testLoadsTheActivePaymentMethodsOnlyOnce(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'checkmo'],
            '_2' => ['payment_method' => 'banktransfer'],
            '_3' => ['payment_method' => 'checkmo'],
        ]));
        $this->paymentConfig->expects(self::once())->method('getActiveMethods')->willReturn([]);

        $this->block->getProductPaymentOptions();
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
            ->with(ProductPaymentOptions::XML_PATH_MIN_INSTALLMENT_VALUE, ScopeInterface::SCOPE_STORE)
            ->willReturn($stored);

        self::assertSame($expected, $this->block->getMinInstallmentValue());
    }

    public function testSerializeForScriptCannotCloseTheScriptTag(): void
    {
        $serialized = $this->block->serializeForScript(['content' => '</script><script>alert(1)</script>']);

        self::assertStringNotContainsString('</script>', $serialized);
        self::assertStringNotContainsString('<', $serialized);
    }
}
