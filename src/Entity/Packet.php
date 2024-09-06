<?php

namespace App\Entity;

class Packet
{
    /**
     * @param array<string, float> $weights
     * @param mixed $value
     */
    public function __construct(
        private readonly array $weights,
        private mixed $value
    ) {
    }

    public function getDifference(Packet $packet): float
    {
        $difference = 0.0;
        $otherWeights = $packet->getWeights();

        foreach ($this->weights as $key => $weight) {
            if (isset($otherWeights[$key])) {
                $difference += abs($weight - $otherWeights[$key]);
                unset($otherWeights[$key]);
            } else {
                $difference += abs($weight);
            }
        }

        foreach ($otherWeights as $weight) {
            $difference += abs($weight);
        }

        return $difference;
    }

    public function getWeights(): array
    {
        return $this->weights;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue(mixed $value): self
    {
        $this->value = $value;
        return $this;
    }
}