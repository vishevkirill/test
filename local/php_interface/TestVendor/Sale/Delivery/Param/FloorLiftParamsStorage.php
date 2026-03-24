<?php

namespace TestVendor\Sale\Delivery\Param;

use Bitrix\Main\Context;
use Bitrix\Sale\Order;

final class FloorLiftParamsStorage
{
    private const PROP_FLOOR_NUM = 'DELIVERY_FLOOR_NUM';
    private const PROP_FLOOR_TYPE = 'DELIVERY_FLOOR_DOOR';
    private const PROP_FLOOR_IDS = 'DELIVERY_FLOOR_IDS';

    public static function load(Order $order): FloorLiftParams
    {
        $request = Context::getCurrent()->getRequest();
        $postedOrder = (array) ($request->getPost('order') ?: []);

        $floor = 0;
        $type = '';
        $basketItemIds = [];

        $postedBasketIds = self::normalizeIds(
            $postedOrder['basketDelivery'] ?? $request->getPost('basketDelivery')
        );

        $fallbackBasketIds = [];

        $propertyCollection = $order->getPropertyCollection();

        foreach ($propertyCollection as $property) {
            $code = (string) $property->getField('CODE');
            $postedValue = self::getPostedPropertyValue($request, $postedOrder, $property);
            $currentValue = $property->getValue();

            if ($code === self::PROP_FLOOR_NUM) {
                $value = $postedValue !== null && $postedValue !== '' ? $postedValue : $currentValue;
                $floor = (int) (is_array($value) ? reset($value) : $value);
                continue;
            }

            if ($code === self::PROP_FLOOR_TYPE) {
                $value = $postedValue !== null && $postedValue !== '' ? $postedValue : $currentValue;
                $type = (string) (is_array($value) ? reset($value) : $value);
                continue;
            }

            if ($code === self::PROP_FLOOR_IDS) {
                $value = $postedValue !== null && $postedValue !== '' ? $postedValue : $currentValue;
                $fallbackBasketIds = self::normalizeIds($value);
            }
        }

        if (!empty($postedBasketIds)) {
            $basketItemIds = $postedBasketIds;
        } else {
            $basketItemIds = $fallbackBasketIds;
        }

        return new FloorLiftParams($basketItemIds, $floor, $type);
    }

    private static function getPostedPropertyValue($request, array $postedOrder, $property): mixed
    {
        $propertyId = 0;

        if (method_exists($property, 'getPropertyId')) {
            $propertyId = (int) $property->getPropertyId();
        }

        if ($propertyId <= 0) {
            $propertyId = (int) $property->getField('ORDER_PROPS_ID');
        }

        if ($propertyId <= 0) {
            return null;
        }

        $key = 'ORDER_PROP_' . $propertyId;

        if (array_key_exists($key, $postedOrder)) {
            return $postedOrder[$key];
        }

        return $request->getPost($key);
    }

    /**
     * @param mixed $value
     * @return int[]
     */
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
}