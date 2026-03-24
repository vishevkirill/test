<?php

namespace TestVendor\Main\Handlers;

class MailEventTemplateHandler
{
    public static function handler(&$arFields, &$arTemplate): void
    {
        $orderItems = html_entity_decode($arFields['ORDER_ITEMS']);
        $orderPrice = html_entity_decode($arFields['ORDER_PRICE']);
        $price = html_entity_decode($arFields['PRICE']);
        $orderList = html_entity_decode($arFields['ORDER_LIST']);

        $arFields['ORDER_ITEMS'] = $orderItems;
        $arFields['ORDER_PRICE'] = $orderPrice;
        $arFields['PRICE'] = $price;
        $arFields['ORDER_LIST'] = $orderList;
    }
}