<?php

namespace App\Command;

use App\Entity\Brain;
use App\Entity\Consciousness;
use App\Entity\Packet;
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
        private readonly HubInterface $hub
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $brain = new Brain(10);
        $consciousness = new Consciousness($brain, 5);

        for ($i = 0; $i < 20; $i++) {
            $weights = array_map(
                function ($weight) {
                    return $weight * rand(-1000, 1000) / 1000;
                },
                array_fill(0, 5, 1.0)
            );
            $consciousness->input(new Packet($weights, null));
        }

        $this->hub->publish(
            new Update(
                'networkdata',
                json_encode([
                    'neurons' => array_values(array_map(function ($neuron) {
                        return [
                            'isOrigin' => empty($neuron->getWeights()),
                            'hash' => $neuron->getHash()
                        ];
                    }, $brain->getNeurons())),
                    'connections' => array_map(function ($connection) {
                        return [
                            'source' => $connection['hash1'],
                            'target' => $connection['hash2'],
                            'ttl' => $connection['ttl']
                        ];
                    }, $brain->getUniqueConnections())
                ])
            )
        );

        return Command::SUCCESS;
    }
}