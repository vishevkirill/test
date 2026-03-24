<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Event;
use CCatalogStore;
use CModule;

class OrderAddressUpdateHandler
{
    public static function handler(Event $event)
    {
        CModule::IncludeModule('sale');

        $order = $event->getParameter('ENTITY');
        $shipmentCollection = $order->getShipmentCollection();
        $storeId = 0;

        foreach ($shipmentCollection as $shipment) {
            if (!$shipment->isSystem()) {
                $storeId = $shipment->getStoreId();
            }
        }

        if (!empty($storeId)) {
            $store = CCatalogStore::GetList(
                [
                    'ID' => 'ASC',
                ],
                [
                    'ACTIVE' => 'Y',
                    'ID' => $storeId,
                ],
                false,
                false,
                []
            )->GetNext();

            $address = $store['ADDRESS'];
            if (!empty($address)) {
                $propertyCollection = $order->getPropertyCollection();
                $addressProp = $propertyCollection->getItemByOrderPropertyId(20);

                if (!is_null($addressProp)) {
                    $addressProp->setValue($address);
                }
            }
        }
    }
}