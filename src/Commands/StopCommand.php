<?php
declare(strict_types=1);

namespace Ody\Server\Commands;

use Ody\Core\Foundation\Console\Style;
use Ody\Server\State\HttpServerState;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'server:stop' ,
    description: 'stops the http server')
]
class StopCommand extends Command
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

        $serverState->killProcesses([
            $serverState->getMasterProcessId(),
            $serverState->getManagerProcessId(),
            $serverState->getWatcherProcessId(),
            ...$serverState->getWorkerProcessIds()
        ]);

        $serverState->clearProcessIds();
        sleep(2);

        $io->success('stopping server...' , true);
        return self::SUCCESS;
    }
}
