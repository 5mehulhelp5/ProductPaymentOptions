<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Test\Unit\Model\Config\Source;

use Fineweb\ProductPaymentOptions\Block\ProductPaymentOptions;
use Fineweb\ProductPaymentOptions\Model\Config\Source\PaymentMethods;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Api\Data\PaymentMethodInterface;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentMethodsTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $params = [];

    /**
     * @var PaymentMethodListInterface&MockObject
     */
    private PaymentMethodListInterface $paymentMethodList;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var ScopeConfigInterface&MockObject
     */
    private ScopeConfigInterface $scopeConfig;

    /**
     * @var PaymentMethods
     */
    private PaymentMethods $source;

    protected function setUp(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getParam')->willReturnCallback(fn (string $key): mixed => $this->params[$key] ?? null);

        $this->paymentMethodList = $this->createMock(PaymentMethodListInterface::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);
        $this->scopeConfig = $this->createMock(ScopeConfigInterface::class);

        $this->source = new PaymentMethods(
            $this->paymentMethodList,
            $request,
            $this->storeManager,
            $this->scopeConfig,
            new Json()
        );
    }

    /**
     * @param string $code
     * @param string $title
     * @return PaymentMethodInterface&MockObject
     */
    private function method(string $code, string $title): PaymentMethodInterface
    {
        $method = $this->createMock(PaymentMethodInterface::class);
        $method->method('getCode')->willReturn($code);
        $method->method('getTitle')->willReturn($title);

        return $method;
    }

    private function givenWebsiteWithDefaultStore(string $websiteId, int $defaultStoreId): void
    {
        $group = $this->createMock(GroupInterface::class);
        $group->method('getDefaultStoreId')->willReturn($defaultStoreId);
        $website = $this->createMock(WebsiteInterface::class);
        $website->method('getId')->willReturn((int) $websiteId);
        $website->method('getDefaultGroupId')->willReturn('9');
        $this->storeManager->method('getWebsite')->with($websiteId)->willReturn($website);
        $this->storeManager->method('getGroup')->with('9')->willReturn($group);
    }

    public function testListsTheMethodsActiveInTheDefaultScope(): void
    {
        $this->paymentMethodList->expects(self::once())->method('getActiveList')->with(0)->willReturn([
            $this->method('checkmo', 'Check / Money order'),
            $this->method('free', 'No Payment Information Required'),
        ]);

        self::assertSame([
            ['value' => 'checkmo', 'label' => 'Check / Money order'],
            ['value' => 'free', 'label' => 'No Payment Information Required'],
        ], $this->source->toOptionArray());
    }

    public function testUsesTheDefaultStoreOfTheWebsiteBeingEdited(): void
    {
        $this->params = ['website' => '1'];
        $this->givenWebsiteWithDefaultStore('1', 5);
        $this->paymentMethodList->expects(self::once())->method('getActiveList')->with(5)->willReturn([
            $this->method('banktransfer', 'Bank Transfer Payment'),
        ]);

        self::assertSame(
            [['value' => 'banktransfer', 'label' => 'Bank Transfer Payment']],
            $this->source->toOptionArray()
        );
    }

    public function testUsesTheStoreViewBeingEdited(): void
    {
        $this->params = ['store' => '3', 'website' => '1'];
        $store = $this->createMock(StoreInterface::class);
        $store->method('getId')->willReturn(3);
        $this->storeManager->method('getStore')->with('3')->willReturn($store);
        $this->paymentMethodList->expects(self::once())->method('getActiveList')->with(3)->willReturn([]);

        $this->source->toOptionArray();
    }

    public function testFallsBackToTheDefaultScopeForAnUnknownScope(): void
    {
        $this->params = ['website' => '99'];
        $this->storeManager->method('getWebsite')->willThrowException(new NoSuchEntityException());
        $this->paymentMethodList->expects(self::once())->method('getActiveList')->with(0)->willReturn([]);

        self::assertSame([], $this->source->toOptionArray());
    }

    public function testKeepsSavedMethodsSelectableEvenWhenInactiveInTheScope(): void
    {
        $this->scopeConfig->method('getValue')
            ->with(ProductPaymentOptions::XML_PATH_PAYMENTS, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null)
            ->willReturn((string) json_encode([
                '_1' => ['payment_method' => 'checkmo'],
                '_2' => ['payment_method' => 'banktransfer'],
                '_3' => 'garbage',
                '_4' => ['payment_method' => ''],
            ]));
        $this->paymentMethodList->method('getActiveList')->willReturn([
            $this->method('checkmo', 'Check / Money order'),
        ]);

        self::assertSame([
            ['value' => 'checkmo', 'label' => 'Check / Money order'],
            ['value' => 'banktransfer', 'label' => 'banktransfer (inactive)'],
        ], $this->source->toOptionArray());
    }

    public function testReadsTheSavedMethodsOfTheEditedWebsite(): void
    {
        $this->params = ['website' => '1'];
        $this->givenWebsiteWithDefaultStore('1', 5);
        $this->paymentMethodList->method('getActiveList')->willReturn([]);
        $this->scopeConfig->expects(self::once())
            ->method('getValue')
            ->with(ProductPaymentOptions::XML_PATH_PAYMENTS, ScopeInterface::SCOPE_WEBSITES, '1')
            ->willReturn('{not json');

        self::assertSame([], $this->source->toOptionArray());
    }
}
