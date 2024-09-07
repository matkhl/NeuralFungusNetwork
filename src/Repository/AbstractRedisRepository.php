<?php

namespace App\Repository;

use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Contracts\Cache\ItemInterface;

abstract class AbstractRedisRepository
{
    private static ?RedisAdapter $cache = null;

    private static function initializeCache(): void
    {
        self::$cache = new RedisAdapter(
            RedisAdapter::createConnection($_ENV['REDIS_CONNECTION'])
        );
    }

    private static function getCache(): RedisAdapter
    {
        if (null === self::$cache) {
            self::initializeCache();
        }

        return self::$cache;
    }

    abstract protected function className(): string;

    /**
     * @throws InvalidArgumentException
     */
    public final function save(mixed $data): void
    {
        if (get_class($data) !== $this->className()) return;
        self::getCache()->delete($this->className());
        self::getCache()->get($this->className(), function (ItemInterface $item) use ($data) {
            $item->expiresAfter(3600);
            return $this->serialize($data);
        });
    }

    /**
     * @throws InvalidArgumentException
     */
    public final function load(): mixed
    {
        if (self::getCache()->hasItem($this->className())) {
            return $this->deserialize(self::getCache()->get($this->className(), function (ItemInterface $item) {
                return null;
            }));
        }
        return null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public final function delete(): void
    {
        self::getCache()->delete($this->className());
    }

    abstract protected function serialize(mixed $data): string;

    abstract protected function deserialize(string $data): mixed;
}