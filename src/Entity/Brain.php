<?php

namespace App\Entity;

class Brain
{
    private int $time;

    private Neuron $origin;

    /**
     * @var array<string, array<string, Connection>>
     */
    private array $connections = [];

    /**
     * @var array<string, Neuron>
     */
    private array $neurons = [];

    public function __construct(
        private readonly int $connectionLifetime
    )
    {
        $this->origin = new Neuron([], null);
        $this->neurons[$this->origin->getHash()] = $this->origin;

        $this->time = 0;
    }

    public function increaseTime(): self
    {
        ++$this->time;
        $this->removeExpiredConnections();
        $this->removeOrphanedNeurons();

        return $this;
    }

    public function updateConnectionTimestamp(Neuron|string $neuron1, Neuron|string $neuron2): self
    {
        $neuron1Hash = $this->getNeuronHash($neuron1);
        $neuron2Hash = $this->getNeuronHash($neuron2);

        $connection = $this->getNeuronConnections($neuron1Hash)[$neuron2Hash] ?? null;
        $connection?->updateTimestamp($this->time);

        return $this;
    }

    private function removeExpiredConnections(): void
    {
        $expirationTime = $this->time - $this->connectionLifetime;
        foreach ($this->connections as $fromHash => $toConnections) {
            foreach ($toConnections as $toHash => $connection) {
                if ($connection->getUpdatedAt() <= $expirationTime) {
                    unset($this->connections[$fromHash][$toHash]);
                    unset($this->connections[$toHash][$fromHash]);
                }
            }
            if (empty($this->connections[$fromHash])) {
                unset($this->connections[$fromHash]);
            }
        }
    }

    private function removeOrphanedNeurons(): void
    {
        foreach ($this->neurons as $hash => $neuron) {
            if ($neuron !== $this->origin && !isset($this->connections[$hash])) {
                unset($this->neurons[$hash]);
            }
        }
    }

    public function getOrigin(): Neuron
    {
        return $this->origin;
    }

    /**
     * @return array<string, array<string, Connection>>
     */
    public function getConnections(): array
    {
        return $this->connections;
    }

    /**
     * @return array<array>
     */
    public function getUniqueConnections(): array
    {
        $uniqueConnections = [];
        $processed = [];

        foreach ($this->connections as $hash1 => $connections) {
            foreach ($connections as $hash2 => $connection) {
                $connectionKey = $hash1 < $hash2 ? "$hash1-$hash2" : "$hash2-$hash1";
                if (!isset($processed[$connectionKey])) {
                    $uniqueConnections[] = [
                        'hash1' => $hash1,
                        'hash2' => $hash2,
                        'ttl' => $connection->getUpdatedAt() + $this->connectionLifetime - $this->time
                    ];
                    $processed[$connectionKey] = true;
                }
            }
        }

        return $uniqueConnections;
    }

    /**
     * @return array<string, Connection>
     */
    public function getNeuronConnections(Neuron|string $neuron): array
    {
        $neuronHash = $this->getNeuronHash($neuron);
        return $this->connections[$neuronHash] ?? [];
    }

    private function addConnection(Neuron $neuron1, Neuron $neuron2): void
    {
        $neuron1Hash = $neuron1->getHash();
        $neuron2Hash = $neuron2->getHash();

        $connection = new Connection($neuron1, $neuron2, $this->time);

        $this->connections[$neuron1Hash][$neuron2Hash] = $connection;
        $this->connections[$neuron2Hash][$neuron1Hash] = $connection;
    }

    public function getNeuron(string $hash): ?Neuron
    {
        return $this->neurons[$hash] ?? null;
    }

    /**
     * @return array<string, Neuron>
     */
    public function getNeurons(): array
    {
        return $this->neurons;
    }

    public function addNeuron(Neuron $neuron, Neuron|string $connector, bool $connectToOrigin = false): self
    {
        $connectorHash = $this->getNeuronHash($connector);
        if (!isset($this->neurons[$connectorHash])) {
            return $this;
        }

        $neuronHash = $neuron->getHash();

        if (!array_key_exists($neuronHash, $this->neurons)) {
            $this->neurons[$neuronHash] = $neuron;

            $this->addConnection($neuron, $connector);

            if ($connectToOrigin) {
                $this->addConnection($neuron, $this->getOrigin());
            }
        }

        return $this;
    }

    private function getNeuronHash(Neuron|string $neuron): string
    {
        return $neuron instanceof Neuron ? $neuron->getHash() : $neuron;
    }
}