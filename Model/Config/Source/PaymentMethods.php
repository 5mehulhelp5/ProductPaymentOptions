<?php
/**
 * Copyright © Fineweb. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Fineweb\ProductPaymentOptions\Model\Config\Source;

use Fineweb\ProductPaymentOptions\Block\ProductPaymentOptions;
use InvalidArgumentException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Provides the payment methods that can be picked in the configuration table.
 *
 * The admin has no storefront scope of its own, so the methods are the ones active in the scope being edited (default,
 * website or store view). Methods already saved in that scope stay selectable even when they are no longer active
 * there, otherwise saving the form would silently swap them for the first option.
 */
class PaymentMethods implements OptionSourceInterface
{
    /**
     * @param PaymentMethodListInterface $paymentMethodList
     * @param RequestInterface $request
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param Json $json
     */
    public function __construct(
        private readonly PaymentMethodListInterface $paymentMethodList,
        private readonly RequestInterface $request,
        private readonly StoreManagerInterface $storeManager,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Json $json
    ) {
    }

    /**
     * Retrieve the payment methods as an array of value/label options.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function toOptionArray(): array
    {
        [$scopeType, $scopeCode, $storeId] = $this->getEditedScope();

        $options = [];
        foreach ($this->paymentMethodList->getActiveList($storeId) as $method) {
            $code = (string) $method->getCode();
            $options[$code] = ['value' => $code, 'label' => (string) $method->getTitle()];
        }
        foreach ($this->getSavedMethodCodes($scopeType, $scopeCode) as $code) {
            $options[$code] ??= ['value' => $code, 'label' => (string) __('%1 (inactive)', $code)];
        }

        return array_values($options);
    }

    /**
     * Resolve the configuration scope shown in the admin.
     *
     * Returns the scope type, the scope code and the store id used to read the payment methods.
     *
     * @return array{string, string|null, int}
     */
    private function getEditedScope(): array
    {
        $store = $this->request->getParam('store');
        $website = $this->request->getParam('website');

        try {
            if ($this->isScopeId($store)) {
                $storeId = (int) $this->storeManager->getStore($store)->getId();

                return [ScopeInterface::SCOPE_STORES, (string) $storeId, $storeId];
            }
            if ($this->isScopeId($website)) {
                $websiteModel = $this->storeManager->getWebsite($website);
                $group = $this->storeManager->getGroup((string) $websiteModel->getDefaultGroupId());

                return [
                    ScopeInterface::SCOPE_WEBSITES,
                    (string) $websiteModel->getId(),
                    (int) $group->getDefaultStoreId(),
                ];
            }
        } catch (NoSuchEntityException) {
            // an unknown scope in the URL falls back to the default scope, as the config page itself does
            return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0];
        }

        return [ScopeConfigInterface::SCOPE_TYPE_DEFAULT, null, 0];
    }

    /**
     * Whether a request parameter holds a scope id or code.
     *
     * @param mixed $value
     * @return bool
     * @phpstan-assert-if-true string|int $value
     */
    private function isScopeId(mixed $value): bool
    {
        return (is_string($value) && $value !== '') || is_int($value);
    }

    /**
     * Payment method codes already saved in the table for a scope.
     *
     * @param string $scopeType
     * @param string|null $scopeCode
     * @return string[]
     */
    private function getSavedMethodCodes(string $scopeType, ?string $scopeCode): array
    {
        $saved = $this->scopeConfig->getValue(ProductPaymentOptions::XML_PATH_PAYMENTS, $scopeType, $scopeCode);
        if (!is_string($saved) || $saved === '') {
            return [];
        }

        try {
            $rows = $this->json->unserialize($saved);
        } catch (InvalidArgumentException) {
            return [];
        }

        $codes = [];
        foreach (is_array($rows) ? $rows : [] as $row) {
            if (is_array($row) && is_string($row['payment_method'] ?? null) && $row['payment_method'] !== '') {
                $codes[] = $row['payment_method'];
            }
        }

        return $codes;
    }
}
