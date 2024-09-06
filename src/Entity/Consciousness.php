<?php

namespace App\Entity;

class Consciousness
{
    private Brain $brain;

    /**
     * @var array<Neuron>
     */
    private array $path;

    public function __construct(
        Brain $brain,
        private readonly int $memorySize
    )
    {
        $this->brain = $brain;
        $this->path[] = $this->brain->getOrigin();
    }

    public function input(Packet $packet): self
    {
        while ($nextNeuron = $this->getNextNeuron($packet)) {
            $this->advanceNeuron($nextNeuron);
        }

        $newNeuron = new Neuron($packet->getWeights(), $packet->getValue());

        if ($newNeuron->getHash() != $this->getCurrentNeuron()->getHash()) {
            $this->brain->addNeuron($newNeuron, $this->getCurrentNeuron(), $this->isClosestToOrigin($packet));
            $this->advanceNeuron($newNeuron);
        }

        return $this;
    }

    public function getCurrentNeuron(): Neuron
    {
        return end($this->path);
    }

    private function getNextNeuron(Packet $packet): ?Neuron
    {
        $bestNeuron = null;
        $leastAdjustedDifference = $this->getCurrentNeuron()->getDifference($packet);
        $checkedNeurons = new \SplObjectStorage();

        for ($i = count($this->path) - 1; $i >= 0; $i--) {
            $memoryNeuron = $this->path[$i];

            $distanceFromCurrent = count($this->path) - 1 - $i;
            $adjustmentFactor = ($this->memorySize - $distanceFromCurrent) / $this->memorySize;

            $this->checkNeuronAndNeighbors($memoryNeuron, $packet, $adjustmentFactor, $checkedNeurons, $bestNeuron, $leastAdjustedDifference);
        }

        return $bestNeuron;
    }

    private function checkNeuronAndNeighbors(Neuron $neuron, Packet $packet, float $adjustmentFactor, \SplObjectStorage $checkedNeurons, ?Neuron &$bestNeuron, float &$leastAdjustedDifference): void
    {
        $difference = $neuron->getDifference($packet);

        if (!$checkedNeurons->contains($neuron)) {
            $adjustedDifference = $difference / $adjustmentFactor;

            if ($adjustedDifference < $leastAdjustedDifference) {
                $leastAdjustedDifference = $adjustedDifference;
                $bestNeuron = $neuron;
            }

            $checkedNeurons->attach($neuron);
        }

        foreach ($this->brain->getNeuronConnections($neuron) as $hash => $connection) {
            $connectedNeuron = $this->brain->getNeuron($hash);
            if ($checkedNeurons->contains($connectedNeuron)) {
                continue;
            }

            $checkedNeurons->attach($connectedNeuron);
            $difference = $connectedNeuron->getDifference($packet);
            $adjustedDifference = $difference / $adjustmentFactor;

            if ($adjustedDifference < $leastAdjustedDifference) {
                $leastAdjustedDifference = $adjustedDifference;
                $bestNeuron = $connectedNeuron;
            }
        }
    }

    private function advanceNeuron(Neuron $nextNeuron): void
    {
        $this->brain->updateConnectionTimestamp($this->getCurrentNeuron(), $nextNeuron);
        $this->brain->increaseTime();
        $this->path[] = $nextNeuron;

        while (count($this->path) > $this->memorySize) {
            array_shift($this->path);
        }
    }

    private function isClosestToOrigin(Packet $packet): bool
    {
        $origin = $this->brain->getOrigin();
        $originDifference = $origin->getDifference($packet);

        foreach ($this->brain->getNeuronConnections($origin) as $hash => $connection) {
            $neighborNeuron = $this->brain->getNeuron($hash);
            $neighborDifference = $neighborNeuron->getDifference($packet);

            if ($neighborDifference <= $originDifference) {
                return false;
            }
        }

        return true;
    }
}