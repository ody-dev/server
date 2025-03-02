<?php

namespace Ody\HttpServer\Commands;

use DI\Container;
use Ody\Core\Console\Style;
use Ody\Core\Foundation\App;
use Ody\Core\Foundation\Bootstrap;
use Ody\Core\Server\Dependencies;
use Ody\HttpServer\Server;
use Ody\Swoole\ServerState;
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
    private ServerState $serverState;
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

        $serverState = ServerState::getInstance();
        $this->io = new Style($input, $output);

        if (!$this->canPhpServerRun($input) ||
            !$this->canDaemonRun($input) ||
            !$this->checkSslCertificate() ||
            !Dependencies::check($this->io)
        ) {
            return Command::FAILURE;
        }

        if ($serverState->websocketServerIsRunning()) {
            $this->handleRunningServer($input, $output);
        }

        /*
         * Bootstrap the app and start the server
         */
        Server::init()->createServer(
            Bootstrap::init(App::create(new Container())),
            $input->getOption('daemonize'),
            $input->getOption('watch'),
            $this->io
        )->start();

        return Command::SUCCESS;
    }

    private function handleRunningServer(InputInterface $input, OutputInterface $output): void
    {
        $serverState = ServerState::getInstance();
        $this->io->error('failed to listen server port[' . config('server.host') . ':' . config('server.port') . '], Error: Address already', true);

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Do you want the server to terminate? (defaults to no)',
            ['no', 'yes'],
            0
        );
        $question->setErrorMessage('Your selection is invalid.');

        $answer = $helper->ask($input, $output, $question);


        if ($answer != 'yes') {
            return;
        }

        posix_kill($serverState->getMasterProcessId(), SIGTERM);
        posix_kill($serverState->getManagerProcessId(), SIGTERM);

        $watcherProcessId = $serverState->getWatcherProcessId();
        if (!is_null($watcherProcessId) && posix_kill($watcherProcessId, SIG_DFL)) {
            posix_kill($watcherProcessId, SIGTERM);
        }

        foreach ($serverState->getWorkerProcessIds() as $processId) {
            posix_kill($processId, SIGTERM);
        }

        sleep(1);
    }

    private function canDaemonRun(InputInterface $input): bool
    {
        if ($input->getOption('daemonize') && $input->getOption('watch')) {
            $this->io->error('Cannot use watcher in daemonize mode', true);

            return false;
        }

        return true;
    }

    private function canPhpServerRun(InputInterface $input): bool
    {
        if ($input->getOption('daemonize') && $input->getOption('phpserver')) {
            $this->io->error('Cannot use th PHP server in daemonize mode', true);

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
