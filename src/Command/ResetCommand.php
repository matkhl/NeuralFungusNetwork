<?php

namespace App\Command;

use App\Repository\BrainRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand('app:reset')]
class ResetCommand extends Command
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
        $this->brainRepository->delete();
        $this->hub->publish(
            new Update(
                'networkdata',
                json_encode([
                    'neurons' => [
                        [
                            'isOrigin' => true,
                            'hash' => '40cd750bba9870f18aada2478b24840a',
                            'shortTtl' => 0.0
                        ]
                    ],
                    'connections' => []
                ])
            )
        );

        return Command::SUCCESS;
    }
}