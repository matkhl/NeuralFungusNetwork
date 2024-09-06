<?php

namespace App\Provider;

use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisProvider
{
    private static ?RedisAdapter $cache = null;

    private static function initializeCache(): void
    {
        self::$cache = new RedisAdapter(
            RedisAdapter::createConnection($_ENV['REDIS_CONNECTION'])
        );
    }

    public static function getCache(): RedisAdapter
    {
        if (null === self::$cache) {
            self::initializeCache();
        }

        return self::$cache;
    }
}