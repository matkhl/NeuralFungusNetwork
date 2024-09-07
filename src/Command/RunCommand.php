<?php

namespace App\Command;

use App\Entity\Brain;
use App\Entity\Consciousness;
use App\Entity\Packet;
use App\Repository\BrainRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand('app:run')]
class RunCommand extends Command
{
    public function __construct(
        private readonly HubInterface $hub,
        private readonly BrainRepository $brainRepository
    )
    {
        parent::__construct();
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $brainConnectionLifetime = 2000;
        $shortTermMemorySize = 30;

        $brain = $this->brainRepository->load() ?? new Brain($brainConnectionLifetime);
        $brain->setConnectionLifetime($brainConnectionLifetime);

        $consciousness = new Consciousness($brain, $shortTermMemorySize);

        $batchSize = 10;

        for ($i = 0; $i < 10000; $i++) {
            $weights = array_map(
                function ($weight) {
                    return $weight * floatval(rand(-10, 10));
                },
                array_fill(0, 3, 1.0)
            );
            $consciousness->input(new Packet($weights, $weights));

            if ($i % $batchSize === 0) {
                $shortTermMemory = $consciousness->getPath();
                $this->hub->publish(
                    new Update(
                        'networkdata',
                        json_encode([
                            'neurons' => array_values(array_map(function ($neuron) use ($shortTermMemory, $shortTermMemorySize) {
                                return [
                                    'isOrigin' => empty($neuron->getWeights()),
                                    'hash' => $neuron->getHash(),
                                    'shortTtl' => in_array($neuron, $shortTermMemory) ?
                                        (array_search($neuron, $shortTermMemory) + 1) / $shortTermMemorySize : 0.0,
                                    'value' => $neuron->getValue()
                                ];
                            }, $brain->getNeurons())),
                            'connections' => array_map(function ($connection) use ($brainConnectionLifetime) {
                                return [
                                    'source' => $connection['hash1'],
                                    'target' => $connection['hash2'],
                                    'ttl' => floatval($connection['ttl']) / $brainConnectionLifetime
                                ];
                            }, $brain->getUniqueConnections())
                        ])
                    )
                );
                time_nanosleep(0, 100000000);
            }
        }

        $this->brainRepository->save($brain);

        return Command::SUCCESS;
    }
}