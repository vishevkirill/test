<?php

namespace TestVendor\Sale\PaySystem\Restrictions;

use Bitrix\Sale\Basket;
use Bitrix\Sale\Internals\Entity;
use Bitrix\Sale\Order;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PaymentCollection;
use Bitrix\Sale\Services\Base\Restriction;
use MA\Catalog\Helpers;

/**
 * Ограничение платёжных систем, при наличию в корзине товаров «под заказ»
 */
final class BasketOnOrderItemsRestriction extends Restriction
{
    /**
     * Метод проверки основного условия
     * @param $checkOnOrderItems
     * @param array $restrictionParams
     * @param int $serviceId
     * @return void
     */
    public static function check($checkOnOrderItems, array $restrictionParams, $serviceId = 0)
    {
        if (empty($restrictionParams)) {
            return true;
        }

        if (
            $restrictionParams['ONORDERPRODUCTS_UNAVAILABLE'] == 'Y' &&
            $checkOnOrderItems
        ) {
            $result = false;
        }

        if (
            $restrictionParams['ONORDERPRODUCTS_UNAVAILABLE'] == 'Y' &&
            !$checkOnOrderItems
        ) {
            $result = true;
        }

        if (
            $restrictionParams['ONORDERPRODUCTS_UNAVAILABLE'] == 'N' &&
            $checkOnOrderItems
        ) {
            $result = false;
        }

        return $result;
    }

    /**
     * Название ограничения
     * @return mixed
     */
    public static function getClassTitle(): mixed
    {
        return 'Для товаров не под заказ';
    }

    /**
     * Описание ограничения
     * @return mixed
     */
    public static function getClassDescription(): mixed
    {
        return 'Ограничение по наличию товаров со свойством "Под заказ"';
    }

    protected static function extractParams(Entity $entity): bool|int|null
    {
        if ($entity instanceof Payment) {
            /** @var PaymentCollection $collection */
            $collection = $entity->getCollection();

            /** @var Order $order */
            $order = $collection->getOrder();

            /** И вот тут проверяем, есть ли в заказе заказная позиция */
            $basket = $order->getBasket();

            if ($basket) {
                return self::checkOnOrderItems($basket);
            }

        } else {
            return -1;
        }

        return 0;
    }

    /**
     * Параметры для создания формы ограничения в админке (типа, дефолтных значений и т.д.)
     * @param int $entityId
     * @return array[]
     */
    public static function getParamsStructure($entityId = 0): array
    {
        return [
            "ONORDERPRODUCTS_UNAVAILABLE" => [
                "TYPE" => "Y/N",
                "DEFAULT" => "Y",
                'MIN' => 0,
                "LABEL" => 'Доступно, если в корзине нет товаров со свойством "Под заказ"'
            ],
        ];
    }

    /**
     * Проверяем, есть ли в заказе товары не в наличии
     * @param Basket $basket
     * @return bool
     */
    public static function checkOnOrderItems(Basket $basket): bool
    {
        $isOnOrderItems = false;

        $products = $basket->getBasketItems();
        foreach ($products as $product) {
            $offerId = $product->getProductId();

            $isOnOrderItems = Helpers::checkOnRequestProduct($offerId);
            if ($isOnOrderItems) break;
        }

        return $isOnOrderItems;
    }
}