<?php

declare(strict_types=1);

namespace TestVendor\Aspro\Premier\Product;

use Aspro\Premier\Product\Basket as AsproBasket;
use TestVendor\Catalog\Plait\ProductCalcBlock;

final class Basket extends AsproBasket
{
    /**
     * Метод переопределен для вывода плайта в карточке товара
     *
     * @param $arOptions
     * @return array
     */
    public static function getOptions($arOptions = []): array
    {
        $result = parent::getOptions($arOptions);
        $minPrice = self::getMinPrice($arOptions['ITEM']);

        if ((bool)$arOptions['DETAIL_PAGE']) {
            $result['HTML'] .= ProductCalcBlock::getBlock($minPrice);
        }

        return $result;
    }


    /**
     * Получение минимальной цены
     *
     * @param array $item
     * @return float
     */
    private static function getMinPrice(array $item): float
    {
        if (
            !empty($item['ITEM_PRICES']) &&
            is_array($item['ITEM_PRICES'])
        ) {
            $min = null;

            foreach ($item['ITEM_PRICES'] as $p) {
                if (!is_array($p)) {
                    continue;
                }

                $v = $p['RATIO_PRICE'] ?? $p['PRICE'] ?? null;
                if (!is_numeric($v)) {
                    continue;
                }

                $v = (float)$v;
                $min = $min === null ? $v : min($min, $v);
            }

            if ($min !== null && $min > 0) {
                return $min;
            }
        }

        if (
            !empty($item['MIN_PRICE']) &&
            is_array($item['MIN_PRICE'])
        ) {
            $v = $item['MIN_PRICE']['DISCOUNT_VALUE'] ?? $item['MIN_PRICE']['VALUE'] ?? null;
            if (is_numeric($v)) {
                return (float)$v;
            }
        }

        if (
            !empty($item['OFFERS']) &&
            is_array($item['OFFERS'])
        ) {
            $min = null;

            foreach ($item['OFFERS'] as $offer) {
                if (!is_array($offer)) {
                    continue;
                }

                $v = self::getMinPrice($offer);
                if ($v > 0) {
                    $min = $min === null ? $v : min($min, $v);
                }
            }

            return $min ?? 0.0;
        }

        if (
            !empty($item['SKU']['CURRENT']) &&
            is_array($item['SKU']['CURRENT'])
        ) {
            return self::getMinPrice($item['SKU']['CURRENT']);
        }

        return 0.0;
    }
}