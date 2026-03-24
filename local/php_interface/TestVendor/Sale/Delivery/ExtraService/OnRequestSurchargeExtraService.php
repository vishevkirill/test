<?php

namespace TestVendor\Sale\Delivery\ExtraService;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\ExtraServices\Base;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;
use TestVendor\Catalog\MadeToOrder;
use TestVendor\Sale\Delivery\Calculator\ProductParentResolver;

final class OnRequestSurchargeExtraService extends Base
{
    public static function getClassTitle(): string
    {
        return 'Доставка до Калининграда (наценка за товары под заказ)';
    }

    public static function getClassDescription(): string
    {
        return 'Автоматическая доп. услуга: наценка на товары "под заказ".';
    }

    public function canUserEditValue(): bool
    {
        return false;
    }

    public function canManagerEditValue(): bool
    {
        return true;
    }

    public function getInitial()
    {
        return $this->packValue([
            'version' => 1,
            'active' => false,
            'productIds' => [],
        ]);
    }

    public function setValue($value)
    {
        if (is_array($value)) {
            if (array_key_exists('productIds', $value)) {
                $productIds = $this->normalizeProductIds((array) $value['productIds']);
            } else {
                $productIds = $this->normalizeProductIds($value);
            }

            $this->value = $this->packValue([
                'version' => 1,
                'active' => !empty($productIds),
                'productIds' => $productIds,
            ]);

            return;
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                $this->value = $this->getInitial();
                return;
            }

            $payload = $this->unpackValue($value);
            if ($payload !== []) {
                $productIds = $this->normalizeProductIds((array) ($payload['productIds'] ?? []));

                $this->value = $this->packValue([
                    'version' => 1,
                    'active' => !empty($productIds),
                    'productIds' => $productIds,
                ]);

                return;
            }

            $decodedJson = json_decode($value, true);
            if (is_array($decodedJson)) {
                $productIds = $this->normalizeProductIds((array) ($decodedJson['productIds'] ?? $decodedJson));

                $this->value = $this->packValue([
                    'version' => 1,
                    'active' => !empty($productIds),
                    'productIds' => $productIds,
                ]);

                return;
            }

            $productIds = $this->normalizeProductIds(preg_split('/\s*,\s*/', $value) ?: []);
            $this->value = $this->packValue([
                'version' => 1,
                'active' => !empty($productIds),
                'productIds' => $productIds,
            ]);

            return;
        }

        $this->value = $this->getInitial();
    }

    public function getPayload(): array
    {
        $raw = (string) $this->getValue();
        $payload = $this->unpackValue($raw);

        if ($payload === []) {
            $decodedJson = json_decode($raw, true);
            $payload = is_array($decodedJson) ? $decodedJson : [];
        }

        return [
            'version' => 1,
            'active' => !empty($payload['active']) && !empty($payload['productIds']),
            'productIds' => $this->normalizeProductIds((array) ($payload['productIds'] ?? [])),
        ];
    }

    public function getDisplayValue(): ?string
    {
        $productIds = $this->getProductIds();
        if ($productIds === []) {
            return 'Нет товаров';
        }

        $titles = [];
        $titlesMap = $this->getAvailableBasketProducts();

        foreach ($productIds as $productId) {
            $titles[] = $titlesMap[$productId]['TITLE'] ?? ('ID ' . $productId);
        }

        return implode(', ', $titles);
    }

    public function getViewControl()
    {
        return htmlspecialcharsbx((string) $this->getDisplayValue());
    }

    public function getEditControl($prefix = '', $value = false)
    {
        $name = $prefix !== '' ? $prefix : (string) $this->getId();
        $currentValue = $value !== false ? $value : $this->getValue();
        $selectedProductIds = [];

        if (is_array($currentValue)) {
            $selectedProductIds = $this->normalizeProductIds($currentValue);
        } elseif (is_string($currentValue) && $currentValue !== '') {
            $payload = $this->unpackValue($currentValue);
            if ($payload === []) {
                $decodedJson = json_decode($currentValue, true);
                $payload = is_array($decodedJson) ? $decodedJson : [];
            }
            $selectedProductIds = $this->normalizeProductIds((array) ($payload['productIds'] ?? []));

        $products = $this->getAvailableBasketProducts();

        if ($products === []) {
            return sprintf(
                '<input type="hidden" name="%s" value="%s" />'
                . '<div style="color:#666;">Список товаров доступен только в карточке заказа.</div>',
                htmlspecialcharsbx($name),
                htmlspecialcharsbx(is_string($currentValue) ? $currentValue : (string) $this->getValue())
            );
        }


        return '';
    }

    public function getPriceShipment(Shipment $shipment = null)
    {
        if ($shipment === null) {
            return 0.0;
        }

        $payload = $this->getPayload();
        $selectedProductIds = $this->normalizeProductIds((array) ($payload['productIds'] ?? []));
        if ($selectedProductIds === []) {
            return 0.0;
        }

        $selectedMap = array_fill_keys($selectedProductIds, true);
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        if (!$shipmentItemCollection) {
            return 0.0;
        }

        $sum = 0.0;
        $factor = 0.1;

        foreach ($shipmentItemCollection as $shipmentItem) {
            $basketItem = $shipmentItem->getBasketItem();
            if (!$basketItem) {
                continue;
            }

            $productId = (int) $basketItem->getProductId();
            $resolvedProductId = ProductParentResolver::resolve($productId);
            if ($resolvedProductId <= 0 || !isset($selectedMap[$resolvedProductId])) {
                continue;
            }

            $quantity = (float) $shipmentItem->getQuantity();
            if ($quantity <= 0) {
                continue;
            }

            $price = (float) $basketItem->getPrice();
            $sum += $price * $quantity * $factor;
        }

        return round($sum, 2);
    }

    public function buildServerValue(Shipment $shipment): string
    {
        $shipmentItemCollection = $shipment->getShipmentItemCollection();
        if (!$shipmentItemCollection) {
            return $this->getInitial();
        }

        $productIds = [];
        foreach ($shipmentItemCollection as $shipmentItem) {
            $basketItem = $shipmentItem->getBasketItem();
            if (!$basketItem) {
                continue;
            }

            $productId = ProductParentResolver::resolve((int) $basketItem->getProductId());
            if ($productId <= 0 || !MadeToOrder::checkProduct($productId)) {
                continue;
            }

            $productIds[$productId] = $productId;
        }

        $productIds = array_values($productIds);

        return $this->packValue([
            'version' => 1,
            'active' => !empty($productIds),
            'productIds' => $productIds,
        ]);
    }

    public function isActive(): bool
    {
        return !empty($this->getPayload()['active']);
    }

    public function getProductIds(): array
    {
        return $this->getPayload()['productIds'];
    }

    private function normalizeProductIds(array $ids): array
    {
        $result = [];

        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * @return array<int, array{TITLE: string}>
     */
    private function getAvailableBasketProducts(): array
    {
        $order = $this->resolveCurrentOrder();
        if (!$order) {
            return [];
        }

        $basket = $order->getBasket();
        if (!$basket) {
            return [];
        }

        $products = [];

        foreach ($basket as $basketItem) {
            if ((string) $basketItem->getField('CAN_BUY') === 'N') {
                continue;
            }

            $productId = ProductParentResolver::resolve((int) $basketItem->getProductId());
            if ($productId <= 0) {
                continue;
            }

            $name = (string) $basketItem->getField('NAME');
            $quantity = (float) $basketItem->getQuantity();

            $products[$productId] = [
                'TITLE' => sprintf('%s [ID %d, qty %s]', $name, $productId, rtrim(rtrim((string) $quantity, '0'), '.')),
            ];
        }

        return $products;
    }

    private function resolveCurrentOrder(): ?Order
    {
        if (!Loader::includeModule('sale')) {
            return null;
        }

        $request = Application::getInstance()->getContext()->getRequest();

        foreach (['order_id', 'ORDER_ID'] as $key) {
            $orderId = (int) $request->get($key);
            if ($orderId > 0) {
                $order = Order::load($orderId);
                if ($order) {
                    return $order;
                }
            }
        }

        foreach (['shipment_id', 'SHIPMENT_ID'] as $key) {
            $shipmentId = (int) $request->get($key);
            if ($shipmentId <= 0) {
                continue;
            }

            $shipmentRow = ShipmentTable::getByPrimary($shipmentId)->fetch();
            $orderId = (int) ($shipmentRow['ORDER_ID'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }

            $order = Order::load($orderId);
            if ($order) {
                return $order;
            }
        }

        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        if (mb_strpos($scriptName, 'order_shipment_edit.php') !== false || mb_strpos($scriptName, 'sale_order_shipment_edit.php') !== false) {
            foreach (['id', 'ID'] as $key) {
                $shipmentId = (int) $request->get($key);
                if ($shipmentId <= 0) {
                    continue;
                }

                $shipmentRow = ShipmentTable::getByPrimary($shipmentId)->fetch();
                $orderId = (int) ($shipmentRow['ORDER_ID'] ?? 0);
                if ($orderId <= 0) {
                    continue;
                }

                $order = Order::load($orderId);
                if ($order) {
                    return $order;
                }
            }
        }

        return null;
    }

    private function packValue(array $payload): string
    {
        return base64_encode(
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function unpackValue(string $value): array
    {
        $decoded = base64_decode($value, true);
        if ($decoded === false || $decoded === '') {
            return [];
        }

        $data = json_decode($decoded, true);

        return is_array($data) ? $data : [];
    }
}
