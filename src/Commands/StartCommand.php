<?php

namespace Ody\HttpServer\Commands;

use Ody\Core\Console\Style;
use Ody\Core\Server\Dependencies;
use Ody\Core\Server\Http;
use Ody\Swoole\HotReload\Watcher;
use Ody\Swoole\ServerState;
use Swoole\Process;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

#[AsCommand(
    name: 'server:start',
    description: 'start http server'
)]
class StartCommand extends Command
{
    private ServerState $serverState;
    private Style $io;

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
            'If there is a change in the program code, it applies the changes instantly'
        )->addOption(
            'phpserver',
            'p',
            InputOption::VALUE_NONE,
            'Run on a build in php server'
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
         * delete old routes closure cache files
         * TODO: implement routes cache
         */
//        if (file_exists(storagePath('routes'))) {
//            $dir = storagePath('routes');
//            foreach (array_diff(scandir($dir), ['.', '..']) as $file) {
//                unlink("$dir/$file");
//            }
//        } else {
//            mkdir(storagePath('routes'));
//        }

        /*
         * create listen message
         */
        $protocol = !is_null(config('server.ssl.ssl_cert_file')) && !is_null(config('server.ssl.ssl_cert_file')) ? 'https' : 'http';
        $listenMessage = "listen on $protocol://" . config('server.host') . ':' . config('server.port');

        /*
         * send running server
         * send listen message
         */
        $this->io->success('http server running…');
        $this->io->info($listenMessage, true);

        /*
         * check if exist daemonize not send general information
         */
        if (!$input->getOption('daemonize') && !$input->getOption('phpserver')) {
            /*
             * create socket type of server
             */
            $serverSocketType = match (config('server.sock_type')) {
                SWOOLE_SOCK_TCP => 'TCP',
                SWOOLE_SOCK_UDP => 'UDP',
                default => 'other type'
            };

            /*
             * create general information table
             */
            $table = new Table($output);
            $table
                ->setHeaderTitle('general information')
                ->setHeaders([
                    '<fg=#FFCB8B;options=bold> PHP VERSION </>',
                    '<fg=#FFCB8B;options=bold> ODY VERSION </>',
                    '<fg=#FFCB8B;options=bold> WORKER COUNT </>',
                    '<fg=#FFCB8B;options=bold> SOCKET TYPE </>',
                    '<fg=#FFCB8B;options=bold> WATCH MODE </>'
                ])
                ->setRows([
                    [
                        '<options=bold> ' . PHP_VERSION . '</>',
                        '<options=bold> ' . ODY_VERSION . ' </>',
                        '<options=bold> ' . config('server.additional.worker_num') . '</>',
                        "<options=bold> $serverSocketType</>",
                        $input->getOption('watch') ? '<fg=#C3E88D;options=bold> ACTIVE </>' : "<fg=#FF5572;options=bold> DEACTIVE </>"
                    ],
                ]);
            $table->setHorizontal();
            $table->render();

            /*
             * send info message for stop server
             */
            $this->io->info('Press Ctrl+C to stop the server');

            /*
             * create watcher server
             */
            if ($input->getOption('watch')) {
                (new Process(function (Process $process) use ($serverState) {
                    $serverState->setWatcherProcessId($process->pid);
                    (new Watcher())->start();
                }))->start();
            }
        }

        /*
         * create and start server
         */
        (new Http($input->getOption('phpserver')))
            ->init($input->getOption('daemonize'));

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
