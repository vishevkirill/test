<?php

namespace TestVendor\Sale\Handlers;

use Bitrix\Main\Event;

class OrderSavedHandler
{
    public static function handler(Event $event)
    {
        $isNew = $event->getParameter('IS_NEW');
        $order = $event->getParameter('ENTITY');

        if ($isNew) {
            $paymentCollection = $order->getPaymentCollection();
            foreach ($paymentCollection as $payment) {
                if (!$payment->isInner()) {
                    $ps = $payment->getField('PAY_SYSTEM_ID');
                    $_SESSION['SHOW_SUCCESS_ORDER'] = $order->getId();

                    if ($ps != 18 && $ps != 2) {
                        $_SESSION['SHOW_SUCCESS_ORDER_ON_GET'] = true;
                    }
                    break;
                }
            }
        }
    }
}