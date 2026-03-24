<?php

namespace TestVendor\Catalog;

use CIBlockElement;
use TestVendor\Config;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;

final class MadeToOrder
{
    public static function checkProductOnMadeToOrder(int $productId): array
    {
        $onRequest = self::checkProduct($productId);

        $data = [];
        if ($onRequest) {
            $data['SHOW'] = true;
            $data['CONTENT'] = self::getHtmlBlock();

            return $data;
        } else {
            $data['SHOW'] = false;
            $data['CONTENT'] = '';
            return $data;
        }
    }

    public static function getHtmlBlock(): string
    {
        global $APPLICATION;
        ob_start();
        $APPLICATION->IncludeFile(
            SITE_DIR . 'include/blocks/catalog/made_to_order_text.php',
            [],
            [
                'SHOW_BORDER' => false,
            ]
        );
        return ob_get_clean();
    }

    public static function checkProduct(int $productId): bool
    {
        if (!$productId) {
            return false;
        }

        $dbProduct = CIBlockElement::GetList(
            [],
            [
                'IBLOCK_ID' => IblockHelper::getIdByCode(IblockCodes::CATALOG),
                'ID' => $productId,
            ],
            false,
            false,
            [
                'ID',
                'NAME',
                'PROPERTY_' . Config::CATALOG_MADE_TO_ORDER_PROPERTY_CODE,
            ],
        );

        $arProduct = $dbProduct->fetch();

        return $arProduct['PROPERTY_' . Config::CATALOG_MADE_TO_ORDER_PROPERTY_CODE . '_VALUE'] &&
            $arProduct['PROPERTY_' . Config::CATALOG_MADE_TO_ORDER_PROPERTY_CODE . '_VALUE'] == 'Да';
    }
}