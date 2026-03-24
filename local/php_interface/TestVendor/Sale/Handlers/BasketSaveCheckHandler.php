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

class BasketSaveCheckHandler
{
    public static function handler(Event $event)
    {
        $messageError = [];
        $basket = $event->getParameter('ENTITY');
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

                if (!$productDelay) {
                    if (!$resultRun) {
                        $messageError[] = "<font class='errortext'>Товар ${$productName} отсутствует на складе</font>";
                    }
                }
            }

            if (!empty($messageError)) {
                return new EventResult(
                    EventResult::ERROR,
                    ResultError::create(new Error(implode('<br>', $messageError), 'CHECK_PRODUCT_ERROR_CODE'))
                );
            }
        }
    }
}