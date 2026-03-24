<?php

namespace TestVendor\Sale\Delivery\Calculator;

use Bitrix\Sale\Shipment;
use TestVendor\Catalog\MadeToOrder;

final class OnRequestSurchargeCalculator
{
    private float $percent;
    private string $priceBase;

    /**
     * @param float $percent Процент наценки (например 10.0)
     * @param string $priceBase PRICE|BASE_PRICE
     */
    public function __construct(float $percent = 10.0, string $priceBase = 'PRICE')
    {
        $this->percent = $percent;
        $this->priceBase = $priceBase;
    }

    public function calculate(Shipment $shipment): float
    {
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        if (!$shipmentItemCollection) {
            return 0.0;
        }

        $sum = 0.0;
        $factor = $this->percent / 100.0;

        foreach ($shipmentItemCollection as $shipmentItem) {
            $basketItem = $shipmentItem->getBasketItem();
            if (!$basketItem) {
                continue;
            }

            $productId = (int)$basketItem->getProductId();
            $parentId = ProductParentResolver::resolve($productId);
            if ($parentId <= 0 || !MadeToOrder::checkProduct($parentId)) {
                continue;
            }

            $quantity = (float)$shipmentItem->getQuantity();
            if ($quantity <= 0) {
                continue;
            }

            $unitPrice = 0.0;
            if ($this->priceBase === 'BASE_PRICE' && method_exists($basketItem, 'getBasePrice')) {
                $unitPrice = (float)$basketItem->getBasePrice();
            } elseif (method_exists($basketItem, 'getPrice')) {
                $unitPrice = (float)$basketItem->getPrice();
            }

            if ($unitPrice <= 0) {
                continue;
            }

            $sum += $unitPrice * $quantity * $factor;
        }

        return $sum;
    }
}
