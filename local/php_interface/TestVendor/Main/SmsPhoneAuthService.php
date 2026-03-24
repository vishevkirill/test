<?php

namespace TestVendor\Main;

use Bitrix\Main\Controller\PhoneAuth;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Sms\Event as SmsEvent;
use Bitrix\Main\Type\DateTime;
use Bitrix\Highloadblock\HighloadBlockTable;
use CUser;
use TestVendor\Config;

final class SmsPhoneAuthService
{
    /** @var class-string|null */
    private static ?string $dataClass = null;

    public static function send($phone, $uid = false): array
    {
        $res = [];
        $action = true;

        $interval = CUser::PHONE_CODE_RESEND_INTERVAL;
        $dateNow = new DateTime();
        $dateNow->add('-T' . $interval . 'S');

        $repCallError = 'Повторная отправка кода возможна через ';

        $dataClass = self::getDataClass();

        $resDb = $dataClass::getList([
            'order' => [
                'ID' => 'DESC',
            ],
            'filter' => [
                'UF_PHONE' => $phone,
            ],
        ]);

        if ($call = $resDb->fetch()) {
            if ($call['UF_DATE']->getTimestamp() > $dateNow->getTimestamp()) {
                $diff = $call['UF_DATE']->getTimestamp() - $dateNow->getTimestamp();
                $action = false;

                $res = [
                    'MESSAGE' => $repCallError . $diff . ' с',
                    'TYPE' => 'ERROR',
                ];
            }
        }

        if ($action) {
            $code = randString(6, ['0123456789']);

            $sms = new SmsEvent(
                Config::PHONE_AUTH_SMS_EVENT_NAME,
                [
                    'USER_PHONE' => $phone,
                    'CODE' => $code,
                ]
            );

            $sms->setSite(Config::PHONE_AUTH_SITE_ID);
            $smsResult = $sms->send(true);

            $signedData = PhoneAuth::signData(['phoneNumber' => $phone, 'uid' => $uid]);

            if ($smsResult->isSuccess()) {
                $lastCalls = 0;

                $res = [
                    'MESSAGE' => 'sms_sent',
                    'TYPE' => 'OK',
                    'SIGNED_DATA' => $signedData,
                    // 'CODE' => $code,
                    'LAST_CALLS' => $lastCalls,
                    'INTERVAL' => $interval,
                ];

                $date = new DateTime();
                $dataClass::add([
                    'UF_PHONE' => $phone,
                    'UF_CODE' => $code,
                    'UF_DATE' => $date,
                    'UF_ATTEMPTS' => 0,
                ]);
            } else {
                $res = [
                    'MESSAGE' => $smsResult->getErrorMessages(),
                    'TYPE' => 'ERROR',
                ];
            }
        }

        return $res;
    }

    public static function verify($code, $phone): array
    {
        $res = [];

        $dataClass = self::getDataClass();

        $dateNow = new DateTime();
        $dateNow->add('-T5M');

        $resDb = $dataClass::getList([
            'order' => [
                'ID' => 'DESC',
            ],
            'filter' => [
                'UF_PHONE' => $phone,
                '>=UF_DATE' => $dateNow,
            ],
        ]);

        if ($call = $resDb->fetch()) {
            if ($call['UF_ATTEMPTS'] == 5) {
                $res = [
                    'MESSAGE' => 'Превышено количество попыток проверки кода. Запросите новый код.',
                    'TYPE' => 'ERROR',
                ];
            } else {
                if ($code == $call['UF_CODE']) {
                    $dataClass::delete($call['ID']);

                    $res = [
                        'TYPE' => 'OK',
                    ];
                } else {
                    $dataClass::update($call['ID'], ['UF_ATTEMPTS' => $call['UF_ATTEMPTS'] + 1]);

                    $res = [
                        'MESSAGE' => 'Неверный код. Попробуйте еще раз.',
                        'TYPE' => 'ERROR',
                    ];
                }
            }
        } else {
            $res = [
                'MESSAGE' => 'Неверный код. Попробуйте еще раз.',
                'TYPE' => 'ERROR',
            ];
        }

        return $res;
    }

    private static function getDataClass(): DataManager|string|null
    {
        if (self::$dataClass !== null) {
            return self::$dataClass;
        }

        Loader::includeModule('highloadblock');

        $hlblock = HighloadBlockTable::resolveHighloadblock(Config::PHONE_AUTH_HL_BLOCK_NAME);
        $entity = HighloadBlockTable::compileEntity($hlblock);

        self::$dataClass = $entity->getDataClass();

        return self::$dataClass;
    }
}