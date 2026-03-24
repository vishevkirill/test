<?php

namespace TestVendor\Iblock\Handlers;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Type\DateTime;
use TestVendor\Iblock\ArResult\Context;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use Throwable;

class AddNewTagProductHandler
{
    public static function handler(Event $event): EventResult
    {
        $context = $event->getParameter('context');

        if (!$context instanceof Context) {
            return new EventResult(EventResult::ERROR);
        }

        $arParams = (array)($context->meta['arParams'] ?? []);

        if (($arParams['IBLOCK_ID'] ?? null) != IblockHelper::getIdByCode(IblockCodes::CATALOG)) {
            return new EventResult(EventResult::SUCCESS);
        }

        if (!empty((array)$context->arResult['ITEMS'])) {
            foreach ($context->arResult['ITEMS'] as $key => $item) {
                $context->arResult['ITEMS'][$key] = self::addSticker($item);
            }
        } else {
            $context->arResult = self::addSticker($context->arResult);
        }

        return new EventResult(EventResult::SUCCESS);
    }

    private static function addSticker(array $item): array
    {
        try {
            $dateCreate = (string)($item['DATE_CREATE'] ?? '');
            if ($dateCreate === '') {
                return $item;
            }

            $createdAt = new DateTime($dateCreate, 'd.m.Y H:i:s');

            $monthAgo = new DateTime();
            $monthAgo->add('-1 month');

            if ($createdAt->getTimestamp() < $monthAgo->getTimestamp()) {
                return $item;
            }

            $item['PROPERTIES']['HIT']['VALUE_XML_ID'] = array_filter((array)$item['PROPERTIES']['HIT']['VALUE_XML_ID']);
            $item['PROPERTIES']['HIT']['VALUE'] = array_filter((array)$item['PROPERTIES']['HIT']['VALUE']);

            $item['PROPERTIES']['HIT']['VALUE_XML_ID'][] = 'NEW';
            $item['PROPERTIES']['HIT']['VALUE'][] = 'Новинка';

            return $item;
        } catch (Throwable) {
            return $item;
        }
    }
}