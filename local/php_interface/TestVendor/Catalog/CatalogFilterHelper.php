<?php

namespace TestVendor\Catalog;

use TestVendor\Config;

class CatalogFilterHelper
{
    public static function getFilterOnRequest(array $arSection, array $arParams): array
    {
        return [
            'SECTION_ID' => $arSection['ID'],
            'ACTIVE' => 'Y',
            'INCLUDE_SUBSECTIONS' => $arParams['INCLUDE_SUBSECTIONS'],
            'IBLOCK_ID' => $arParams['IBLOCK_ID'],
            [
                'LOGIC' => 'OR',
                [
                    '>CATALOG_QUANTITY' => 0,
                ],
                [
                    'CATALOG_QUANTITY' => 0,
                    'PROPERTY_' . Config::CATALOG_MADE_TO_ORDER_PROPERTY_CODE . '_VALUE' => 'Да',
                ]
            ]
        ];
    }
}