<?php

namespace Ody\Server\Commands;

use Ody\Core\Foundation\Console\Style;
use Ody\Server\State\HttpServerState;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'server:reload' ,
    description: 'reload http server'
)]
class ReloadCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $serverState = HttpServerState::getInstance();
        $io = new Style($input, $output);

        if (!$serverState->httpServerIsRunning()){
            $io->error('server is not running...' , true);
            return self::FAILURE;
        }

        $serverState->reloadProcesses([
            $serverState->getMasterProcessId(),
            $serverState->getManagerProcessId(),
            ...$serverState->getWorkerProcessIds()
        ]);

        $io->success('reloading workers...' , true);
        return self::SUCCESS;
    }
}