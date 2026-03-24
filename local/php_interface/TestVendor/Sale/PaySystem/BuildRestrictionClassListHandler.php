<?php

namespace TestVendor\Sale\PaySystem;

use Bitrix\Main\EventResult;
use TestVendor\Sale\PaySystem\Restrictions\BasketOnOrderItemsRestriction;

class BuildRestrictionClassListHandler
{
    public static function handler(): EventResult
    {
        return new EventResult(
            EventResult::SUCCESS,
            [
                ltrim(BasketOnOrderItemsRestriction::class, '\\') => '/local/php_interface/TestVendor/Sale/PaySystem/Restrictions/BasketOnOrderItemsRestriction.php',
            ]
        );
    }
}