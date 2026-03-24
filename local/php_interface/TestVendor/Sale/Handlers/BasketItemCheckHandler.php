<?php

namespace TestVendor\Sale\Handlers;

use CIBlockElement;
use TestVendor\Config;
use TestVendor\Iblock\IblockCodes;
use TestVendor\Iblock\IblockHelper;
use TestVendor\OneC\Exchange;

class BasketItemCheckHandler
{
    public static function handler(&$arFields)
    {
        $productId = $arFields['PRODUCT_ID'];
        $productQuantity = $arFields['QUANTITY'];
        $productDelay = $arFields['DELAY'];

        if ($productId) {
            $element = CIBlockElement::GetByID($productId)->GetNext();

            $methodOneC = 'SkaldNomTestVendor';

            $config = [
                'url' => Config::EXCHANGE_ONEC_URL_NEW . $methodOneC,
                'login' => Config::EXCHANGE_ONEC_LOGIN,
                'password' => Config::EXCHANGE_ONEC_PASSWORD,
            ];

            $method = 'checkProductQuantity';
            $params = [
                'id' => $productId,
                'quantity' => $productQuantity,
            ];

            $classExchange = new Exchange($config, IblockHelper::getIdByCode(IblockCodes::CATALOG), $method, $params);
            $resultRun = $classExchange->run();

            if ($productDelay !== 'Y') {
                if (!$resultRun) {
                    $messageArr = [
                        'STATUS' => 'ERROR',
                        'MESSAGE' => 'ERROR_ADD2BASKET',
                        'MESSAGE_EXT' => "<font class='errortext'>Товар ${$element['NAME']} отсутствует на складе</font>"
                    ];

                    echo json_encode($messageArr, JSON_UNESCAPED_UNICODE);
                    exit();
                }
            }
        }
    }
}