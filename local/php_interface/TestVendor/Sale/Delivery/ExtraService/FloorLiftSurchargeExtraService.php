<?php

namespace TestVendor\Sale\Delivery\ExtraService;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Sale\Delivery\ExtraServices\Checkbox;
use Bitrix\Sale\Internals\ShipmentTable;
use Bitrix\Sale\Order;
use Bitrix\Sale\Shipment;
use TestVendor\Sale\Delivery\Calculator\FloorLiftSurchargeCalculator;
use TestVendor\Sale\Delivery\Param\FloorLiftParams;

final class FloorLiftSurchargeExtraService extends Checkbox
{
    public static function getClassTitle(): string
    {
        return 'Подъём / доставка до двери';
    }

    public static function getClassDescription(): string
    {
        return 'Автоматическая доп. услуга: стоимость подъёма по параметрам заказа.';
    }

    public function canUserEditValue(): bool
    {
        return false;
    }

    public function canManagerEditValue(): bool
    {
        return true;
    }

    public function getInitial(): ?string
    {
        return $this->packValue([
            'version' => 1,
            'active' => false,
            'basketItemIds' => [],
            'floor' => 0,
            'type' => '',
        ]);
    }

    public function setValue($value)
    {
        if (is_array($value)) {
            if (
                array_key_exists('basketItemIds', $value)
                || array_key_exists('floor', $value)
                || array_key_exists('type', $value)
            ) {
                $payload = [
                    'basketItemIds' => (array) ($value['basketItemIds'] ?? []),
                    'floor' => $value['floor'] ?? 0,
                    'type' => $value['type'] ?? '',
                ];
            } else {
                $payload = $value;
            }

            $this->value = $this->packValue($this->normalizePayload($payload));
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
                $this->value = $this->packValue($this->normalizePayload($payload));
                return;
            }

            $decodedJson = json_decode($value, true);
            if (is_array($decodedJson)) {
                $this->value = $this->packValue($this->normalizePayload($decodedJson));
                return;
            }
        }

        $this->value = $this->getInitial();
    }

    public function getPriceShipment(Shipment $shipment = null)
    {
        if ($shipment === null) {
            return 0.0;
        }

        $payload = $this->getPayload();
        if (empty($payload['active'])) {
            return 0.0;
        }

        $params = new FloorLiftParams(
            $payload['basketItemIds'],
            $payload['floor'],
            $payload['type']
        );

        return (float) (new FloorLiftSurchargeCalculator())->calculate($shipment, $params);
    }

    public function buildServerValue(FloorLiftParams $params): string
    {
        return $this->packValue($this->normalizePayload([
            'version' => 1,
            'active' => true,
            'basketItemIds' => $params->basketItemIds,
            'floor' => $params->floor,
            'type' => $params->type,
        ]));
    }

    public function getPayload(): array
    {
        $value = $this->getValue();
        if (!is_string($value) || $value === '') {
            return $this->normalizePayload([]);
        }

        $payload = $this->unpackValue($value);
        if ($payload !== []) {
            return $this->normalizePayload($payload);
        }

        $decodedJson = json_decode($value, true);

        return is_array($decodedJson) ? $this->normalizePayload($decodedJson) : $this->normalizePayload([]);
    }

    public function getDisplayValue(): ?string
    {
        $payload = $this->getPayload();
        if (empty($payload['active'])) {
            return 'Не выбрано';
        }

        $typeLabel = $payload['type'] === 'LIFT' ? 'На лифте' : 'По лестнице';
        $itemsMap = $this->getAvailableBasketItems();
        $itemTitles = [];

        foreach ((array) $payload['basketItemIds'] as $basketItemId) {
            $basketItemId = (int) $basketItemId;
            $itemTitles[] = $itemsMap[$basketItemId]['TITLE'] ?? ('Позиция #' . $basketItemId);
        }

        return sprintf(
            '%s, этаж %d, товары: %s',
            $typeLabel,
            (int) $payload['floor'],
            $itemTitles !== [] ? implode(', ', $itemTitles) : 'не выбраны'
        );
    }

    public function getViewControl()
    {
        return htmlspecialcharsbx((string) $this->getDisplayValue());
    }

    public function getEditControl($prefix = '', $value = false)
    {
        return '';
    }

    private function normalizePayload(array $payload): array
    {
        $basketItemIds = [];

        foreach ((array) ($payload['basketItemIds'] ?? []) as $basketItemId) {
            $basketItemId = (int) $basketItemId;
            if ($basketItemId > 0) {
                $basketItemIds[$basketItemId] = $basketItemId;
            }
        }

        $basketItemIds = array_values($basketItemIds);
        $floor = max(0, (int) ($payload['floor'] ?? 0));
        $type = (string) ($payload['type'] ?? '');

        if (!in_array($type, ['STAIRS', 'LIFT'], true)) {
            $type = '';
        }

        return [
            'version' => 1,
            'active' => !empty($basketItemIds) && $floor > 0 && $type !== '',
            'basketItemIds' => $basketItemIds,
            'floor' => $floor,
            'type' => $type,
        ];
    }

    /**
     * @return array<int, array{TITLE: string}>
     */
    private function getAvailableBasketItems(): array
    {
        $order = $this->resolveCurrentOrder();
        if (!$order) {
            return [];
        }

        $basket = $order->getBasket();
        if (!$basket) {
            return [];
        }

        $items = [];

        foreach ($basket as $basketItem) {
            if ((string) $basketItem->getField('CAN_BUY') === 'N') {
                continue;
            }

            $basketItemId = (int) $basketItem->getId();
            if ($basketItemId <= 0) {
                continue;
            }

            $name = (string) $basketItem->getField('NAME');
            $quantity = (float) $basketItem->getQuantity();
            $items[$basketItemId] = [
                'TITLE' => sprintf(
                    '%s [позиция #%d, qty %s]',
                    $name,
                    $basketItemId,
                    rtrim(rtrim((string) $quantity, '0'), '.')
                ),
            ];
        }

        return $items;
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
