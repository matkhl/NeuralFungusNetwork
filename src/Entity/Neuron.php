<?php

namespace App\Entity;

class Neuron extends Packet
{
    private string $hash;

    /**
     * @param array<float> $weights
     * @param mixed $value
     */
    public function __construct(
        array $weights,
        mixed $value
    ) {
        parent::__construct($weights, $value);
        $this->hash = $this->createHash($weights);
    }

    /**
     * @param array<string, float> $weights
     */
    protected function createHash(array $weights): string
    {
        ksort($weights);
        return md5(serialize($weights));
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}