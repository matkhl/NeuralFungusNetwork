<?php

namespace App\Entity;

class Connection
{
    private int $createdAt;
    private int $updatedAt;

    public function __construct(
        private readonly Neuron $neuron1,
        private readonly Neuron $neuron2,
        int $timestamp
    ) {
        $this->createdAt = $timestamp;
        $this->updatedAt = $this->createdAt;
    }

    public function getNeuron1(): Neuron
    {
        return $this->neuron1;
    }

    public function getNeuron2(): Neuron
    {
        return $this->neuron2;
    }

    public function getCreatedAt(): int
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): int
    {
        return $this->updatedAt;
    }

    public function updateTimestamp(int $timestamp): void
    {
        $this->updatedAt = $timestamp;
    }
}