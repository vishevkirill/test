<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Event;
use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Delivery\Services\Pickup;
use TestVendor\Config;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use TestVendor\OneC\Exchange;

class OrderDeliveryPriceHandler
{
    private const YANDEX_HANDLER_CLASS = 'Corsik\\YaDelivery\\Delivery\\YandexDeliveryHandler';

    public static function handler(Event $event)
    {
        $shipmentID = 2;
        $order = $event->getParameter('ENTITY');

        if (!$order) {
            return;
        }

        $propertyCollection = $order->getPropertyCollection();
        $shipmentCollection = $order->getShipmentCollection();

        $deliveryLocationZip = $propertyCollection->getDeliveryLocationZip();
        $zip = !empty($deliveryLocationZip) ? trim((string) $deliveryLocationZip->getValue()) : '';

        if ($zip === '') {
            return;
        }

        $methodOneC = 'PriceToAdress';

        $config = [
            'url' => Config::EXCHANGE_ONEC_URL . $methodOneC,
            'login' => Config::EXCHANGE_ONEC_LOGIN,
            'password' => Config::EXCHANGE_ONEC_PASSWORD,
        ];

        $method = 'getDeliveryPriceByZip';
        $params = [
            'zip' => $zip,
        ];

        foreach ($shipmentCollection as $shipment) {
            $shipmentId = (int) $shipment->getId();

            if ($shipment->isSystem() && $shipmentId !== $shipmentID) {
                continue;
            }

            if (self::isYandexCourierShipment($shipment)) {
                continue;
            }

            $classExchange = new Exchange(
                $config,
                IblockHelper::getIdByCode(IblockCodes::CATALOG),
                $method,
                $params
            );

            $price = (float) $classExchange->run();

            if ($price <= 0) {
                continue;
            }

            $shipment->setFields([
                'PRICE_DELIVERY' => $price,
                'CUSTOM_PRICE_DELIVERY' => 'Y',
            ]);
        }
    }

    private static function isYandexCourierShipment($shipment): bool
    {
        $deliveryId = (int) $shipment->getField('DELIVERY_ID');
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

        $className = ltrim(get_class($deliveryService), '\\');
        if ($className === self::YANDEX_HANDLER_CLASS) {
            return true;
        }

        if (method_exists($deliveryService, 'getFields')) {
            $fields = (array) $deliveryService->getFields();
            if (!empty($fields['CLASS_NAME']) && ltrim((string) $fields['CLASS_NAME'], '\\') === self::YANDEX_HANDLER_CLASS) {
                return true;
            }
        }

        if (method_exists($deliveryService, 'getConfigValues')) {
            $config = (array) $deliveryService->getConfigValues();

            if (!empty($config['MAIN']['CLASS_NAME']) && ltrim((string) $config['MAIN']['CLASS_NAME'], '\\') === self::YANDEX_HANDLER_CLASS) {
                return true;
            }

            if (!empty($config['CLASS_NAME']) && ltrim((string) $config['CLASS_NAME'], '\\') === self::YANDEX_HANDLER_CLASS) {
                return true;
            }
        }

        return false;
    }
}