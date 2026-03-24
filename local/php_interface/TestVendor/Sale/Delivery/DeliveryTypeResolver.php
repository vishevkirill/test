<?php

declare(strict_types=1);

namespace TestVendor\Sale\Delivery;

use Bitrix\Sale\Delivery\Services\Manager as DeliveryManager;
use Bitrix\Sale\Delivery\Services\Pickup;

final class DeliveryTypeResolver
{
    public const TYPE_COURIER = 'courier';
    public const TYPE_PICKUP = 'pickup';
    public const TYPE_OTHER = 'other';

    private const YANDEX_HANDLER_CLASS = '\\Corsik\\YaDelivery\\Delivery\\YandexDeliveryHandler';

    public static function resolveByServiceId(int $deliveryId): string
    {
        if ($deliveryId <= 0) {
            return self::TYPE_OTHER;
        }

        $deliveryService = DeliveryManager::getObjectById($deliveryId);

        return self::resolveByService($deliveryService);
    }

    public static function isCourierByServiceId(int $deliveryId): bool
    {
        return self::resolveByServiceId($deliveryId) === self::TYPE_COURIER;
    }

    public static function isCourier(object|null $deliveryService): bool
    {
        return self::resolveByService($deliveryService) === self::TYPE_COURIER;
    }

    public static function getHandlerClassByServiceId(int $deliveryId): string
    {
        if ($deliveryId <= 0) {
            return '';
        }

        $deliveryService = DeliveryManager::getObjectById($deliveryId);

        return self::extractHandlerClass($deliveryService);
    }

    public static function resolveByService(object|null $deliveryService): string
    {
        if (!$deliveryService) {
            return self::TYPE_OTHER;
        }

        if ($deliveryService instanceof Pickup) {
            return self::TYPE_PICKUP;
        }

        $handlerClass = self::normalizeClassName(self::extractHandlerClass($deliveryService));

        if ($handlerClass !== '' && $handlerClass === self::normalizeClassName(self::YANDEX_HANDLER_CLASS)) {
            return self::TYPE_COURIER;
        }

        if (method_exists($deliveryService, 'getStoresList')) {
            $stores = $deliveryService->getStoresList();
            if (is_array($stores) && !empty($stores)) {
                return self::TYPE_PICKUP;
            }
        }

        return self::TYPE_OTHER;
    }

    private static function extractHandlerClass(object|null $deliveryService): string
    {
        if (!$deliveryService) {
            return '';
        }

        /**
         * 1. Иногда getObjectById уже возвращает сам handler-класс.
         */
        $className = get_class($deliveryService);
        if (self::looksLikeYandexHandler($className)) {
            return $className;
        }

        /**
         * 2. Иногда CLASS_NAME лежит в полях сервиса.
         */
        if (method_exists($deliveryService, 'getFields')) {
            $fields = (array) $deliveryService->getFields();

            if (!empty($fields['CLASS_NAME']) && is_string($fields['CLASS_NAME'])) {
                return $fields['CLASS_NAME'];
            }
        }

        /**
         * 3. Иногда класс лежит в конфиге.
         */
        if (method_exists($deliveryService, 'getConfigValues')) {
            $config = (array) $deliveryService->getConfigValues();

            if (!empty($config['MAIN']['CLASS_NAME']) && is_string($config['MAIN']['CLASS_NAME'])) {
                return $config['MAIN']['CLASS_NAME'];
            }

            if (!empty($config['CLASS_NAME']) && is_string($config['CLASS_NAME'])) {
                return $config['CLASS_NAME'];
            }
        }

        return $className;
    }

    private static function looksLikeYandexHandler(string $className): bool
    {
        return self::normalizeClassName($className) === self::normalizeClassName(self::YANDEX_HANDLER_CLASS);
    }

    private static function normalizeClassName(string $className): string
    {
        return ltrim(trim($className), '\\');
    }
}