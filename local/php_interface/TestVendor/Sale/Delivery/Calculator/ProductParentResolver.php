<?php

namespace TestVendor\Sale\Delivery\Calculator;

use Bitrix\Main\Loader;
use CCatalogSku;

final class ProductParentResolver
{
    /**
     * @var array<int, int>
     */
    private static array $cache = [];

    public static function resolve(int $productId): int
    {
        if ($productId <= 0) {
            return 0;
        }

        if (isset(self::$cache[$productId])) {
            return self::$cache[$productId];
        }

        $resolvedId = $productId;

        if (Loader::includeModule('catalog')) {
            $info = CCatalogSku::GetProductInfo($productId);
            if (is_array($info) && !empty($info['ID'])) {
                $resolvedId = (int)$info['ID'];
            }
        }

        self::$cache[$productId] = $resolvedId;

        return $resolvedId;
    }
}
