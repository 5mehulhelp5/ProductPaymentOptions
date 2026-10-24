<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\ViewModel;

use Fineweb\ProductPaymentOptions\Model\Config;
use Fineweb\ProductPaymentOptions\ViewModel\PaymentOptions;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Serialize\Serializer\JsonHexTag;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentOptionsTest extends TestCase
{
    private const STORE_ID = 7;

    /**
     * @var Config&MockObject
     */
    private Config $config;

    /**
     * @var PaymentMethodListInterface&MockObject
     */
    private PaymentMethodListInterface $paymentMethodList;

    /**
     * @var PaymentOptions
     */
    private PaymentOptions $viewModel;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Config::class);
        $this->paymentMethodList = $this->createMock(PaymentMethodListInterface::class);
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(self::STORE_ID);
        $storeManager = $this->createMock(StoreManagerInterface::class);
        $storeManager->method('getStore')->willReturn($store);

        $this->viewModel = new PaymentOptions(
            $this->config,
            $this->paymentMethodList,
            $storeManager,
            new Json(),
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
     * @param array<string, mixed> $rows
     */
    private function givenSavedRows(array $rows): void
    {
        $this->config->method('getPayments')->willReturn((string) json_encode($rows));
    }

    public function testIsAViewModel(): void
    {
        self::assertInstanceOf(ArgumentInterface::class, $this->viewModel);
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function unusablePaymentsProvider(): array
    {
        return [
            'not configured' => [null],
            'invalid json' => ['{not json'],
            'scalar json' => ['42'],
        ];
    }

    #[DataProvider('unusablePaymentsProvider')]
    public function testReturnsNullWhenThePaymentsAreNotUsable(?string $stored): void
    {
        $this->config->method('getPayments')->willReturn($stored);

        self::assertNull($this->viewModel->getOptions());
    }

    public function testResolvesPaymentMethodCodesToTitles(): void
    {
        $this->givenSavedRows([
            '_1' => ['payment_method' => 'checkmo', 'discount_percentage' => '5', 'max_installments' => '1'],
            '_2' => ['payment_method' => 'banktransfer', 'max_installments' => '6'],
        ]);
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
            $this->paymentMethod('banktransfer', 'Bank Transfer Payment'),
        ]);

        $options = $this->viewModel->getOptions();

        self::assertIsArray($options);
        self::assertSame(['_1', '_2'], array_keys($options));
        self::assertSame('Check / Money order', $options['_1']['payment_method']);
        self::assertSame('Bank Transfer Payment', $options['_2']['payment_method']);
        self::assertSame('5', $options['_1']['discount_percentage']);
    }

    public function testListsOnlyMethodsActiveInTheCurrentStore(): void
    {
        $this->givenSavedRows([
            '_1' => ['payment_method' => 'checkmo'],
            '_2' => ['payment_method' => 'disabled_method'],
        ]);
        $this->paymentMethodList->expects(self::once())
            ->method('getActiveList')
            ->with(self::STORE_ID)
            ->willReturn([$this->paymentMethod('checkmo', 'Check / Money order')]);

        $options = $this->viewModel->getOptions();

        self::assertIsArray($options);
        self::assertSame(['_1'], array_keys($options));
    }

    public function testUsesTheCodeAsTitleWhenTheActiveMethodHasNoTitle(): void
    {
        $this->givenSavedRows(['_1' => ['payment_method' => 'untitled']]);
        $this->paymentMethodList->method('getActiveList')->willReturn([$this->paymentMethod('untitled', '')]);

        $options = $this->viewModel->getOptions();

        self::assertIsArray($options);
        self::assertSame('untitled', $options['_1']['payment_method']);
    }

    public function testFillsMissingRowKeysSoTheTemplateNeverReadsAnUndefinedIndex(): void
    {
        $this->givenSavedRows(['_1' => ['payment_method' => 'checkmo']]);
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

        $options = $this->viewModel->getOptions();

        self::assertIsArray($options);
        self::assertSame('', $options['_1']['payment_method_content']);
        self::assertSame('', $options['_1']['monthly_interest_rate']);
    }

    public function testSkipsRowsThatAreNotArraysOrHaveANonStringPaymentMethod(): void
    {
        $this->givenSavedRows([
            '_1' => 'garbage',
            '_2' => ['payment_method' => ['nested']],
            '_3' => ['payment_method' => 'checkmo'],
        ]);
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

        $options = $this->viewModel->getOptions();

        self::assertIsArray($options);
        self::assertSame(['_3'], array_keys($options));
    }

    public function testMinInstallmentValueIsZeroWhenNotConfigured(): void
    {
        $this->config->method('getMinInstallmentValue')->willReturn(null);

        self::assertSame(0.0, $this->viewModel->getMinInstallmentValue());
    }

    public function testMinInstallmentValueIsTheConfiguredOne(): void
    {
        $this->config->method('getMinInstallmentValue')->willReturn(150.0);

        self::assertSame(150.0, $this->viewModel->getMinInstallmentValue());
    }

    public function testInitConfigTargetsTheWidgetElementAndCarriesTheOptions(): void
    {
        $this->givenSavedRows(['_1' => ['payment_method' => 'checkmo', 'max_installments' => '6']]);
        $this->config->method('getMinInstallmentValue')->willReturn(150.0);
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

        $config = json_decode($this->viewModel->getInitConfig(), true);

        self::assertIsArray($config);
        $widget = $config['[data-role="fineweb-payment-options"]']['finewebProductPaymentOptions'];
        self::assertEquals(150, $widget['minInstallmentValue']);
        self::assertSame('Check / Money order', $widget['paymentOptions']['_1']['payment_method']);
        self::assertSame('6', $widget['paymentOptions']['_1']['max_installments']);
    }

    public function testInitConfigCannotCloseTheScriptTag(): void
    {
        $this->givenSavedRows(['_1' => ['payment_method' => 'checkmo', 'payment_method_content' => '</script>']]);
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->paymentMethod('checkmo', 'Check / Money order'),
        ]);

        self::assertStringNotContainsString('</script>', $this->viewModel->getInitConfig());
        self::assertStringNotContainsString('<', $this->viewModel->getInitConfig());
    }
}
