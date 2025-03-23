<?php

namespace Ody\Server\Concerns;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

class HasRunningServer
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param $serverState
     * @return void
     */
    private function handleRunningServer(InputInterface $input, OutputInterface $output, $serverState): void
    {
        $this->io->error('failed to listen server port[' . config('server.host') . ':' . config('server.port') . '], Error: Address already', true);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Do you want the scheduler to terminate? (defaults to no)',
            ['no', 'yes'],
            0
        );
        $question->setErrorMessage('Your selection is invalid.');

        if ($helper->ask($input, $output, $question) !== 'yes') {
            return;
        }

        $serverState->killProcesses([
            $serverState->getMasterProcessId(),
            $serverState->getManagerProcessId(),
            $serverState->getWatcherProcessId(),
            ...$serverState->getWorkerProcessIds()
        ]);

        $serverState->clearProcessIds();

        sleep(2);
    }
}