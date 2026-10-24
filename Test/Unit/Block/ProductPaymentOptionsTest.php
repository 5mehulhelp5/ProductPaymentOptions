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
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ProductPaymentOptionsTest extends TestCase
{
    private const STORE_ID = 7;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var PaymentMethodListInterface&MockObject
     */
    private PaymentMethodListInterface $paymentMethodList;

    /**
     * @var ProductPaymentOptions
     */
    private ProductPaymentOptions $block;

    protected function setUp(): void
    {
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $this->paymentMethodList = $this->createMock(PaymentMethodListInterface::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);
        $json = new Json();

        $this->block = new ProductPaymentOptions(
            $this->createMock(Context::class),
            $this->scopeConfig,
            $this->paymentMethodList,
            $storeManager,
            $json,
            new JsonHexTag()
        );
    }

    /**
     * @param string $code
     * @param string $title
     * @return PaymentMethodInterface&MockObject
     */
    private function paymentMethod(string $code, string $title): PaymentMethodInterface
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->method('getCode')->willReturn($code);
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
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
            $this->paymentMethod('banktransfer', 'Bank Transfer Payment'),
        ]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame(['_1', '_2'], array_keys($options));
        self::assertSame('Check / Money order', $options['_1']['payment_method']);
        self::assertSame('Bank Transfer Payment', $options['_2']['payment_method']);
        self::assertSame('5', $options['_1']['discount_percentage']);
    }

    public function testListsOnlyMethodsActiveInTheCurrentStore(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'checkmo'],
            '_2' => ['payment_method' => 'disabled_method'],
        ]));
        $this->paymentMethodList->expects(self::once())
            ->method('getActiveList')
            ->with(self::STORE_ID)
            ->willReturn([$this->paymentMethod('checkmo', 'Check / Money order')]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame(['_1'], array_keys($options));
    }

    public function testUsesTheCodeAsTitleWhenTheActiveMethodHasNoTitle(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'untitled'],
        ]));
        $this->paymentMethodList->method('getActiveList')->willReturn([$this->paymentMethod('untitled', '')]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame('untitled', $options['_1']['payment_method']);
    }

    public function testFillsMissingRowKeysSoTheTemplateNeverReadsAnUndefinedIndex(): void
    {
        $this->scopeConfig->method('getValue')->willReturn((string) json_encode([
            '_1' => ['payment_method' => 'checkmo'],
        ]));
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

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
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

        $options = $this->block->getProductPaymentOptions();

        self::assertIsArray($options);
        self::assertSame(['_2'], array_keys($options));
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
