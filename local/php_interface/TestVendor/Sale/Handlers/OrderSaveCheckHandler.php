<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Sale\ResultError;
use TestVendor\Config;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use TestVendor\OneC\Exchange;
use TestVendor\Sale\Delivery\DeliveryTypeResolver;
use TestVendor\Sale\Delivery\Param\FloorLiftParamsStorage;

class OrderSaveCheckHandler
{
    public static function handler(Event $event)
    {
        $messageError = [];
        $order = $event->getParameter('ENTITY');
        $basket = $order->getBasket();
        $methodOneC = 'SkaldNomTestVendor';

        $config = [
            'url' => Config::EXCHANGE_ONEC_URL_NEW . $methodOneC,
            'login' => Config::EXCHANGE_ONEC_LOGIN,
            'password' => Config::EXCHANGE_ONEC_PASSWORD,
        ];

        $method = 'checkProductQuantity';

        if (!empty($basket)) {
            foreach ($basket as $basketItem) {
                $productName = $basketItem->getField('NAME');
                $productId = $basketItem->getField('PRODUCT_ID');
                $productQuantity = $basketItem->getField('QUANTITY');
                $productDelay = $basketItem->isDelay();

                $params = [
                    'id' => $productId,
                    'quantity' => $productQuantity,
                ];

                $classExchange = new Exchange($config, IblockHelper::getIdByCode(IblockCodes::CATALOG), $method, $params);
                $resultRun = $classExchange->run();

                if (!$productDelay && !$resultRun) {
                    $messageError[] = "<font class='errortext'>Товар {$productName} отсутствует на складе</font>";
                }
            }
        }

        $floorParams = FloorLiftParamsStorage::load($order);

        if (self::hasCourierShipment($order) && !empty($floorParams->basketItemIds) && (int) $floorParams->floor <= 0) {
            $messageError[] = "<font class='errortext'>Укажите этаж для курьерской доставки</font>";
        }

        if (!empty($messageError)) {
            return new EventResult(
                EventResult::ERROR,
                ResultError::create(new Error(implode('<br>', $messageError), 'CHECK_PRODUCT_ERROR_CODE'))
            );
        }
    }

    private static function hasCourierShipment($order): bool
    {
        foreach ($order->getShipmentCollection() as $shipment) {
            if ($shipment->isSystem()) {
                continue;
            }

            $deliveryId = (int) $shipment->getField('DELIVERY_ID');

            if ($deliveryId > 0 && DeliveryTypeResolver::isCourierByServiceId($deliveryId)) {
                return true;
            }
        }

        return false;
    }
}