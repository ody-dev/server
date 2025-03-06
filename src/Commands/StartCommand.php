<?php

namespace Ody\Server\Commands;

use Ody\Core\Foundation\Console\Style;
use Ody\Core\Foundation\Http\Server;
use Ody\Core\Server\Dependencies;
use Ody\Server\ServerManager;
use Ody\Server\ServerType;
use Ody\Server\State\HttpServerState;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/*
 * delete old routes closure cache files
 * TODO: implement routes cache
 */

#[AsCommand(
    name: 'server:start',
    description: 'Start a http server'
)]
class StartCommand extends Command
{
    private HttpServerState $serverState;
    private SymfonyStyle $io;

    protected function configure(): void
    {
        $this->addOption(
            'daemonize',
            'd',
            InputOption::VALUE_NONE,
            'The program works in the background'
        )->addOption(
            'watch',
            'w',
            InputOption::VALUE_NONE,
            'Enable a file watcher. Set directories to be watched in your server.php config file.'
        );
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $serverState = HttpServerState::getInstance();
        $this->io = new Style($input, $output);

        if (!$this->canDaemonRun($input) ||
            !$this->checkSslCertificate() ||
            !Dependencies::check($this->io)
        ) {
            return Command::FAILURE;
        }

        if ($serverState->httpServerIsRunning()) {
            $this->handleRunningServer($input, $output);
        }

        Server::start(
            ServerManager::init(ServerType::HTTP_SERVER, HttpServerState::getInstance())
                ->createServer(config('server'))
                ->setServerConfig(config('server.additional'))
                ->registerCallbacks(config('server.callbacks'))
                ->daemonize($input->getOption('daemonize'))
                ->getServerInstance()
        );

        return Command::SUCCESS;
    }

    private function handleRunningServer(InputInterface $input, OutputInterface $output): void
    {
        $this->io->error('failed to listen server port[' . config('server.host') . ':' . config('server.port') . '], Error: Address already', true);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Do you want the server to terminate? (defaults to no)',
            ['no', 'yes'],
            0
        );
        $question->setErrorMessage('Your selection is invalid.');

        if ($helper->ask($input, $output, $question) !== 'yes') {
            return;
        }

        $serverState = HttpServerState::getInstance();
        $serverState->killProcesses([
            $serverState->getMasterProcessId(),
            $serverState->getManagerProcessId(),
            $serverState->getWatcherProcessId(),
            ...$serverState->getWorkerProcessIds()
        ]);

        $serverState->clearProcessIds();

        sleep(2);
    }

    private function canDaemonRun(InputInterface $input): bool
    {
        if ($input->getOption('daemonize') && $input->getOption('watch')) {
            $this->io->error('Cannot use watcher in daemonize mode', true);

            return false;
        }

        return true;
    }

    private function checkSslCertificate(): bool
    {
        if (!is_null(config('server.ssl.ssl_cert_file')) && !file_exists(config('server.ssl.ssl_cert_file'))) {
            $this->io->error("ssl certificate file is not found", true);
            return false;
        }

        if (!is_null(config('server.ssl.ssl_cert_file')) && !file_exists(config('server.ssl.ssl_cert_file'))) {
            $this->io->error("ssl key file is not found", true);
            return false;
        }

        return true;
    }
}
