<?php

namespace TestVendor\Iblock\ArResult;

use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class Dispatcher
{
    public const EVENT_NAME = 'testVendorModificationArResult';

    public static function apply(array $arResult, array $meta = []): array
    {
        $context = new Context($arResult, $meta);

        $event = new Event(
            'iblock',
            self::EVENT_NAME,
            [
                'context' => $context,
            ],
        );

        EventManager::getInstance()->send($event);

        return $context->arResult;
    }
}