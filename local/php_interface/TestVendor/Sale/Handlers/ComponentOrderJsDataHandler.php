<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Catalog\GroupTable;
use Bitrix\Catalog\PriceTable;
use Bitrix\Main\Context;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Delivery\Services\Pickup;
use CCurrencyLang;
use CCatalogSku;
use TestVendor\Catalog\MadeToOrder;
use TestVendor\Config;
use TestVendor\Sale\Delivery\Calculator\FreeFloorLiftResolver;
use TestVendor\Sale\Delivery\Calculator\ProductParentResolver;

class ComponentOrderJsDataHandler
{
    private const YANDEX_HANDLER_CLASS = 'Corsik\YaDelivery\Delivery\YandexDeliveryHandler';

    public static function handler(&$arResult, &$arParams)
    {
        global $USER;

        $request = Context::getCurrent()->getRequest();
        $postedOrder = (array)($request->getPost('order') ?: []);

        $arResult['JS_DATA']['NEED_PHONE_CONF'] = false;
        $arResult['JS_DATA']['PHONE_DISABLED'] = false;
        $arResult['JS_DATA']['PHONE_PROP_ID'] = false;

        $isAuthorized = $USER->IsAuthorized();

        if ($request->getRequestMethod() === 'GET') {
            unset($_SESSION['ORDER_PHONE_CONFED']);
        }

        if (!$isAuthorized) {
            if (empty($_SESSION['ORDER_PHONE_CONFED'])) {
                $arResult['JS_DATA']['NEED_PHONE_CONF'] = true;
            } else {
                foreach (($arResult['JS_DATA']['ORDER_PROP']['properties'] ?? []) as $i => $prop) {
                    if (($prop['CODE'] ?? '') === 'PHONE') {
                        if (
                            ($prop['VALUE'] ?? '') === '' ||
                            (is_array($prop['VALUE'] ?? null) && (count($prop['VALUE']) === 0 || $prop['VALUE'][0] === ''))
                        ) {
                            $arResult['JS_DATA']['ORDER_PROP']['properties'][$i]['VALUE'] = [$_SESSION['ORDER_PHONE_CONFED']];
                        }

                        $arResult['JS_DATA']['PHONE_DISABLED'] = true;
                        $arResult['JS_DATA']['PHONE_PROP_ID'] = $prop['ID'];
                        break;
                    }
                }
            }
        }

        $productCount = [];
        $productIds = [];
        $discontSum = 0;
        $baseSum = 0;
        $basketCount = 0;

        foreach (($arResult['BASKET_ITEMS'] ?? []) as $item) {
            if (($item['DELAY'] ?? 'N') === 'N' && ($item['CAN_BUY'] ?? 'N') === 'Y') {
                $productCount[] = [
                    'ID' => (int)$item['PRODUCT_ID'],
                    'QUANTITY' => (float)$item['QUANTITY'],
                    'PRICE' => (float)$item['PRICE'],
                    'BASE_PRICE' => (float)$item['BASE_PRICE'],
                ];
                $productIds[] = (int)$item['PRODUCT_ID'];
                $basketCount += (float)$item['QUANTITY'];
            }
        }

        if ($productIds) {
            $oldPriceTypeId = 0;
            $oldPriceCache = [];

            if (class_exists('\Bitrix\Catalog\GroupTable')) {
                $priceType = GroupTable::getRow([
                    'select' => ['ID'],
                    'filter' => ['=NAME' => Config::PRICE_OLD],
                ]);

                $oldPriceTypeId = (int)($priceType['ID'] ?? 0);
            }

            foreach ($productCount as $prod) {
                $productId = (int)$prod['ID'];
                $quantity = (float)$prod['QUANTITY'];
                $basePrice = (float)$prod['BASE_PRICE'];

                if (!isset($oldPriceCache[$productId])) {
                    $priceOldValue = 0.0;
                    $priceProductId = $productId;

                    if (class_exists('CCatalogSku')) {
                        $parentInfo = CCatalogSku::GetProductInfo($priceProductId);
                        if (is_array($parentInfo) && !empty($parentInfo['ID'])) {
                            $priceProductId = (int)$parentInfo['ID'];
                        }
                    }

                    if ($oldPriceTypeId > 0 && class_exists('\Bitrix\Catalog\PriceTable')) {
                        $priceRow = PriceTable::getRow([
                            'select' => ['PRICE'],
                            'filter' => [
                                '=PRODUCT_ID' => $priceProductId,
                                '=CATALOG_GROUP_ID' => $oldPriceTypeId,
                            ],
                            'order' => ['QUANTITY_FROM' => 'ASC', 'QUANTITY_TO' => 'ASC', 'ID' => 'ASC'],
                        ]);

                        if ($priceRow) {
                            $priceOldValue = (float)$priceRow['PRICE'];
                        }
                    }

                    $oldPriceCache[$productId] = $priceOldValue;
                }

                $oldPrice = (float)$oldPriceCache[$productId];

                $displayBasePrice = $oldPrice > $basePrice ? $oldPrice : $basePrice;
                $baseSum += $displayBasePrice * $quantity;

                if ($oldPrice > $basePrice) {
                    $discontSum += ($oldPrice - $basePrice) * $quantity;
                }
            }
        }

        foreach (($arResult['JS_DATA']['GRID']['ROWS'] ?? []) as $i => $row) {
            $row['data']['SIZE'] = '';

            if (($row['data']['DIMENSIONS'] ?? '') !== '') {
                $dimensions = is_array($row['data']['DIMENSIONS'])
                    ? $row['data']['DIMENSIONS']
                    : unserialize($row['data']['DIMENSIONS'], ['allowed_classes' => false]);

                $sizeArray = [];

                if (!empty($dimensions['WIDTH'])) {
                    $sizeArray[] = $dimensions['WIDTH'] / 10;
                }
                if (!empty($dimensions['HEIGHT'])) {
                    $sizeArray[] = $dimensions['HEIGHT'] / 10;
                }
                if (!empty($dimensions['LENGTH'])) {
                    $sizeArray[] = $dimensions['LENGTH'] / 10;
                }

                if ($sizeArray) {
                    $row['data']['SIZE'] = implode(' x ', $sizeArray) . ' см';
                }
            }

            $arResult['JS_DATA']['GRID']['ROWS'][$i] = $row;
        }

        $arResult['JS_DATA']['TOTAL']['PRODUCT_BASE_SUM'] = CCurrencyLang::CurrencyFormat($baseSum, 'RUB');
        $arResult['JS_DATA']['TOTAL']['DISCOUNT_PRICE'] += $discontSum;
        $arResult['JS_DATA']['TOTAL']['DISCOUNT_PRICE_FORMATED'] = CCurrencyLang::CurrencyFormat($arResult['JS_DATA']['TOTAL']['DISCOUNT_PRICE'], 'RUB');
        $arResult['JS_DATA']['TOTAL']['BASKET_COUNT_STR'] = $basketCount . ' ' . self::numberEnding((int)$basketCount, ['товар', 'товара', 'товаров']);

        foreach (($arResult['JS_DATA']['DELIVERY'] ?? []) as $i => $del) {
            $deliveryId = (int)($del['ID'] ?? 0);
            $handlerClass = self::resolveDeliveryHandlerClass($deliveryId);
            $isCourier = self::isCourierDeliveryId($deliveryId);

            $arResult['JS_DATA']['DELIVERY'][$i]['NAME'] = trim(str_replace('(Профиль)', '', (string)$del['NAME']));
        }

        $selectedDelivery = self::findSelectedDelivery($arResult['JS_DATA']['DELIVERY'] ?? []);

        $orderPropsByCode = self::extractOrderPropsByCode($arResult);
        $floorIds = self::readFloorIds($postedOrder, $orderPropsByCode);

        $onRequestSum = $extraServicePrices['ON_REQUEST'];
        $floorLiftSum = $extraServicePrices['FLOOR_LIFT'];

        $deliveryPrice = (float)($arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE'] ?? 0);
        if ($deliveryPrice <= 0 && is_array($selectedDelivery)) {
            $deliveryPrice = (float)($selectedDelivery['PRICE'] ?? 0);
        }

        $courierBase = $deliveryPrice > 0
            ? max(0.0, $deliveryPrice - $onRequestSum - $floorLiftSum)
            : 0.0;

        $currency = (string)($arResult['JS_DATA']['TOTAL']['CURRENCY'] ?? 'RUB');

        $arResult['JS_DATA']['DELIVERY_FLOOR_IDS'] = $floorIds;

    }

    private static function findSelectedDelivery(array $deliveries): ?array
    {
        foreach ($deliveries as $delivery) {
            if (($delivery['CHECKED'] ?? 'N') === 'Y') {
                return $delivery;
            }
        }

        return null;
    }

    private static function extractBasketRows(array $arResult): array
    {
        $rows = [];

        foreach (($arResult['JS_DATA']['GRID']['ROWS'] ?? []) as $row) {
            $data = $row['data'] ?? $row;

            $basketId = (int)($data['ID'] ?? $row['ID'] ?? 0);
            $productId = (int)($data['PRODUCT_ID'] ?? 0);
            $quantity = (float)($data['QUANTITY'] ?? 0);
            $price = (float)($data['PRICE'] ?? 0);

            if ($basketId <= 0 || $productId <= 0 || $quantity <= 0) {
                continue;
            }

            $rows[] = [
                'BASKET_ID' => $basketId,
                'PRODUCT_ID' => $productId,
                'QUANTITY' => $quantity,
                'PRICE' => $price,
            ];
        }

        return $rows;
    }

    private static function calculateOnRequestSum(array $basketRows): float
    {
        $sum = 0.0;
        $cache = [];

        foreach ($basketRows as $row) {
            $productId = (int)$row['PRODUCT_ID'];
            $parentId = ProductParentResolver::resolve($productId);

            if ($parentId <= 0) {
                $parentId = $productId;
            }

            if (!array_key_exists($parentId, $cache)) {
                $cache[$parentId] = MadeToOrder::checkProduct($parentId);
            }

            if (!$cache[$parentId]) {
                continue;
            }

            $sum += (float)$row['QUANTITY'] * (float)$row['PRICE'] * 0.10;
        }

        return $sum;
    }

    private static function calculateFloorLiftSum(
        array $basketRows,
        array $selectedIds,
        int $floorNum,
        string $floorType,
        bool $isCourier,
        bool $hasCalcError
    ): float {
        if (!$isCourier || $hasCalcError || $floorNum <= 0 || !$selectedIds) {
            return 0.0;
        }

        if (!in_array($floorType, ['STAIRS', 'LIFT'], true)) {
            return 0.0;
        }

        $floorPrice = 0;

        if ($floorType === 'LIFT') {
            $floorPrice = 200;
        } elseif ($floorType === 'STAIRS') {
            $floorPrice = $floorNum * 100;
        }

        if ($floorPrice <= 0) {
            return 0.0;
        }

        $selectedMap = array_fill_keys($selectedIds, true);
        $sum = 0.0;

        foreach ($basketRows as $row) {
            $basketId = (int)$row['BASKET_ID'];
            if (!isset($selectedMap[$basketId])) {
                continue;
            }

            $productId = (int)$row['PRODUCT_ID'];
            if (FreeFloorLiftResolver::isFreeForProduct($productId)) {
                continue;
            }

            $sum += (float)$row['QUANTITY'] * $floorPrice;
        }

        return $sum;
    }

    private static function extractOrderPropsByCode(array $arResult): array
    {
        $result = [];

        foreach (($arResult['JS_DATA']['ORDER_PROP']['properties'] ?? []) as $prop) {
            $code = (string)($prop['CODE'] ?? '');
            if ($code === '') {
                continue;
            }

            $result[$code] = [
                'ID' => (int)($prop['ID'] ?? 0),
                'VALUE' => $prop['VALUE'] ?? '',
            ];
        }

        return $result;
    }

    private static function readFloorIds(array $postedOrder, array $orderPropsByCode): array
    {
        $postedBasket = self::normalizeIds($postedOrder['basketDelivery'] ?? null);
        if ($postedBasket) {
            return $postedBasket;
        }

        $propValue = self::readOrderPropByCode('DELIVERY_FLOOR_IDS', $postedOrder, $orderPropsByCode);

        return self::normalizeIds($propValue);
    }

    private static function readOrderPropByCode(string $code, array $postedOrder, array $orderPropsByCode): mixed
    {
        if (!isset($orderPropsByCode[$code])) {
            return '';
        }

        $propId = (int)($orderPropsByCode[$code]['ID'] ?? 0);

        if ($propId > 0) {
            $key = 'ORDER_PROP_' . $propId;

            if (array_key_exists($key, $postedOrder) && $postedOrder[$key] !== '') {
                return $postedOrder[$key];
            }
        }

        return $orderPropsByCode[$code]['VALUE'] ?? '';
    }

    private static function normalizeScalar(mixed $value): string
    {
        if (is_array($value)) {
            $value = reset($value);
        }

        if ($value === null) {
            return '';
        }

        return is_scalar($value) ? trim((string)$value) : '';
    }

    private static function normalizeIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_unique(array_filter(array_map('intval', $value))));
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        if ($value[0] === '[') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return array_values(array_unique(array_filter(array_map('intval', $decoded))));
            }
        }

        $parts = preg_split('/\s*,\s*/', $value);
        if (!is_array($parts)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $parts))));
    }

    private static function resolveDeliveryHandlerClass(int $deliveryId): string
    {
        if ($deliveryId <= 0) {
            return '';
        }

        $deliveryService = DeliveryManager::getObjectById($deliveryId);
        if (!$deliveryService) {
            return '';
        }

        $className = ltrim(get_class($deliveryService), '\\');
        if ($className === self::YANDEX_HANDLER_CLASS) {
            return $className;
        }

        if (method_exists($deliveryService, 'getFields')) {
            $fields = (array)$deliveryService->getFields();
            if (!empty($fields['CLASS_NAME']) && is_string($fields['CLASS_NAME'])) {
                return ltrim($fields['CLASS_NAME'], '\\');
            }
        }

        if (method_exists($deliveryService, 'getConfigValues')) {
            $config = (array)$deliveryService->getConfigValues();

            if (!empty($config['MAIN']['CLASS_NAME']) && is_string($config['MAIN']['CLASS_NAME'])) {
                return ltrim($config['MAIN']['CLASS_NAME'], '\\');
            }

            if (!empty($config['CLASS_NAME']) && is_string($config['CLASS_NAME'])) {
                return ltrim($config['CLASS_NAME'], '\\');
            }
        }

        return $className;
    }

    private static function isCourierDeliveryId(int $deliveryId): bool
    {
        if ($deliveryId <= 0) {
            return false;
        }

        $deliveryService = DeliveryManager::getObjectById($deliveryId);
        if (!$deliveryService) {
            return false;
        }

        if ($deliveryService instanceof Pickup) {
            return false;
        }

        return self::resolveDeliveryHandlerClass($deliveryId) === self::YANDEX_HANDLER_CLASS;
    }

    private static function numberEnding($n, $titles)
    {
        $cases = [2, 0, 1, 1, 1, 2];

        return $titles[($n % 100 > 4 && $n % 100 < 20) ? 2 : $cases[min($n % 10, 5)]];
    }
}