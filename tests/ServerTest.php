<?php

use Ody\HttpServer\Server;

define('PROJECT_PATH' , realpath('./tests'));

class ServerTest extends \PHPUnit\Framework\TestCase
{
    public function testHttpClassInitialises()
    {
        $http = new Server();
        $this->assertInstanceOf(Server::class, $http);
    }

    public function testServerStarts()
    {
        $http = new Server();
        $this->assertInstanceOf(Server::class, $http);
    }
}