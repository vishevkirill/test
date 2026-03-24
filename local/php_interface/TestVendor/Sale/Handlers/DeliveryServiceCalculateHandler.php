<?php

declare(strict_types=1);

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use TestVendor\Sale\Delivery\ExtraService\FloorLiftSurchargeExtraService;
use TestVendor\Sale\Delivery\ExtraService\OnRequestSurchargeExtraService;

final class DeliveryServiceCalculateHandler
{
    public static function handler(Event $event): void
    {
        $baseResult = $event->getParameter('RESULT');
        $shipment = $event->getParameter('SHIPMENT');

        if (!$shipment || !$baseResult || !method_exists($baseResult, 'getDeliveryPrice')) {
            return;
        }

        $price = (float) $baseResult->getDeliveryPrice();

        $deliveryId = (int) $shipment->getField('DELIVERY_ID');
        $deliveryService = $deliveryId > 0 ? DeliveryManager::getObjectById($deliveryId) : null;
        if (!$deliveryService) {
            return;
        }

        $extraServices = $deliveryService->getExtraServices();
        if (!$extraServices || !method_exists($extraServices, 'getItems')) {
            return;
        }

        $shipmentExtraServices = [];
        if (method_exists($shipment, 'getExtraServices')) {
            $shipmentExtraServices = (array) $shipment->getExtraServices();
        }

        if (!empty($shipmentExtraServices) && method_exists($extraServices, 'setValues')) {
            $extraServices->setValues($shipmentExtraServices);
        }

        foreach ((array) $extraServices->getItems() as $extraService) {
            if (!$extraService instanceof OnRequestSurchargeExtraService && !$extraService instanceof FloorLiftSurchargeExtraService) {
                continue;
            }

            $extraPrice = (float) $extraService->getPriceShipment($shipment);
            if ($extraPrice <= 0) {
                continue;
            }

            $price += $extraPrice;
        }

        $baseResult->setDeliveryPrice($price);

        $event->addResult(
            new EventResult(
                EventResult::SUCCESS,
                [
                    'RESULT' => $baseResult,
                ]
            )
        );
    }
}
