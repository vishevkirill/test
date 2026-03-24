<?php

namespace TestVendor\Agents;

use CEventLog;
use Exception;
use TestVendor\Config;
use TestVendor\OneC\Exchange;
use Throwable;

abstract class BaseExchangeAgent
{
    abstract protected function getExchangeMethod(): string;

    protected function getEndpoint(): string
    {
        return Config::ENDPOINT_STOCK_LIST;
    }

    public static function run(): string
    {
        try {
            $agent = new static();
            $agent->execute();
        } catch (Throwable $e) {
            CEventLog::Add([
                'SEVERITY' => 'ERROR',
                'AUDIT_TYPE_ID' => 'AGENT_ERROR',
                'MODULE_ID' => 'main',
                'ITEM_ID' => static::class,
                'DESCRIPTION' => $e->getMessage(),
            ]);
        }

        return static::class . '::run();';
    }

    /**
     * @throws Exception
     */
    public function execute(): void
    {
        if (!class_exists(Exchange::class)) {
            throw new Exception('Класс Exchange не найден.');
        }

        $fullUrl = Config::EXCHANGE_ONEC_URL . $this->getEndpoint();

        $config = [
            'url' => $fullUrl,
            'login' => Config::EXCHANGE_ONEC_LOGIN,
            'password' => Config::EXCHANGE_ONEC_PASSWORD,
        ];

        $exchange = new Exchange($config, $this->getExchangeMethod());
        $exchange->run();
    }
}