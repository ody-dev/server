<?php

use Ody\Server\Server;
use Swoole\Process;
use Swoole\Runtime;

class ServerTest extends \PHPUnit\Framework\TestCase
{
    public function testHttpClassInitialises()
    {
        $http = new Server();
        $this->assertInstanceOf(Server::class, $http);
    }

    public function testServerStartsWithConfiguration(): void
    {
        $process = new Process(function (Process $worker): void {
            $server = new Server();
            $server = $server->createServer(false, false);
            $server->start();

            $serverInfo = [
                "masterProcessId" => $server->getServer()->getMasterPid(),
                "managerProcessId" => $server->getServer()->getManagerPid()
            ];

            $worker->write(json_encode($serverInfo));
            $worker->exit(0);
        });
        $process->start();
        $serverInfo = json_decode($process->read());
        $serverState = json_decode(
            file_get_contents(storagePath('serverState.json'))
        );

        $this->assertSame($serverInfo->masterProcessId, $serverState->pIds->masterProcessId);
        $this->assertSame($serverInfo->managerProcessId, $serverState->pIds->managerProcessId);
        Process::wait(true);
    }

    public function testCanEnableCoroutines(): void
    {
        Runtime::enableCoroutine();
        $i = 0;
        go(static function () use (&$i): void {
            usleep(1000);
            ++$i;
        });
        go(function () use (&$i): void {
            ++$i;
            $this->assertEquals(1, $i);
        });
    }
}