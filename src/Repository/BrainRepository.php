<?php

namespace App\Repository;

use App\Entity\Brain;

class BrainRepository extends AbstractRedisRepository
{

    protected function className(): string
    {
        return Brain::class;
    }

    /**
     * @param Brain $data
     */
    protected function serialize(mixed $data): string
    {
        return serialize($data);
    }

    protected function deserialize(string $data): ?Brain
    {
        return unserialize($data) ?? null;
    }
}