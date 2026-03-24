<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use TestVendor\Sale\Delivery\ExtraService\FloorLiftSurchargeExtraService;
use TestVendor\Sale\Delivery\ExtraService\OnRequestSurchargeExtraService;

/**
 * Регистрирует кастомные классы доп. услуг доставки (extra services).
 */
final class DeliveryExtraServicesClassNamesBuildListHandler
{
    public static function handler(Event $event): EventResult
    {
        return new EventResult(
            EventResult::SUCCESS,
            [
                ltrim(OnRequestSurchargeExtraService::class, '\\') => '/local/php_interface/TestVendor/Sale/Delivery/ExtraService/OnRequestSurchargeExtraService.php',
                ltrim(FloorLiftSurchargeExtraService::class, '\\') => '/local/php_interface/TestVendor/Sale/Delivery/ExtraService/FloorLiftSurchargeExtraService.php',
            ]
        );
    }
}
