<?php

namespace TestVendor\Sale\Delivery\Calculator;

use Bitrix\Sale\Shipment;
use TestVendor\Sale\Delivery\Param\FloorLiftParams;

final class FloorLiftSurchargeCalculator
{
    private const STAIRS_PRICE_PER_FLOOR = 100;
    private const LIFT_PRICE = 200;

    public function calculate(Shipment $shipment, FloorLiftParams $params): float
    {
        if ($params->floor <= 0) {
            return 0.0;
        }

        if (!in_array($params->type, ['STAIRS', 'LIFT'], true)) {
            return 0.0;
        }

        if (empty($params->basketItemIds)) {
            return 0.0;
        }

        $floorPrice = $this->getFloorPrice($params->type, $params->floor);
        if ($floorPrice <= 0) {
            return 0.0;
        }

        $idSet = array_fill_keys($params->basketItemIds, true);
        $sum = 0.0;

        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        foreach ($shipmentItemCollection as $shipmentItem) {
            $basketItem = $shipmentItem->getBasketItem();
            if (!$basketItem) {
                continue;
            }

            $basketItemId = (int) $basketItem->getId();
            if (!isset($idSet[$basketItemId])) {
                continue;
            }

            $productId = (int) $basketItem->getProductId();
            if (FreeFloorLiftResolver::isFreeForProduct($productId)) {
                continue;
            }

            $quantity = (float) $shipmentItem->getQuantity();
            if ($quantity <= 0) {
                continue;
            }

            $sum += $quantity * $floorPrice;
        }

        return $sum;
    }

    private function getFloorPrice(string $type, int $floor): int
    {
        if ($type === 'LIFT') {
            return self::LIFT_PRICE;
        }

        if ($type !== 'STAIRS') {
            return 0;
        }

        return $floor * self::STAIRS_PRICE_PER_FLOOR;
    }
}