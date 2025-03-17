[![Actions Status](https://github.com/ody-dev/ody-http-server/workflows/Build%20and%20test/badge.svg)](https://github.com/ody-dev/ody-http-server/actions)
[![License](https://poser.pugx.org/ody/core/license)](https://packagist.org/packages/ody/core)

## HTTP server
```
composer require ody/server
```

## Basic usage
### Configuration
When creating a server it requires configuration parameters, below is an example. Read the Swoole docs for additional 
parameters.

```php
$config = [
    'mode' => SWOOLE_BASE,
    'host' => env('HTTP_SERVER_HOST' , '127.0.0.1'),
    'port' => env('HTTP_SERVER_PORT' , 9501) ,
    'sock_type' => SWOOLE_SOCK_TCP,
    'additional' => [
        'daemonize' => false,
        'worker_num' => 8,
        'log_level' => SWOOLE_LOG_DEBUG,
        'log_file' => base_path('/storage/logs/ody_server.log'),
        'enable_coroutine' => true,
        'max_coroutine' => 3000,
    ],
    'callbacks' => [
        ServerEvent::ON_REQUEST => [\Ody\Core\Foundation\Http\Server::class, 'onRequest'],
        ServerEvent::ON_START => [\Ody\Server\ServerCallbacks::class, 'onStart'],
        ServerEvent::ON_WORKER_ERROR => [\Ody\Server\ServerCallbacks::class, 'onWorkerError'],
        ServerEvent::ON_WORKER_START => [\Ody\Server\ServerCallbacks::class, 'onWorkerStart'],
    ],
    'ssl' => [
        'ssl_cert_file' => null ,
        'ssl_key_file' => null ,
    ] 
;
```

### Creating & starting a server

```php
$server = ServerManager::init(ServerType::HTTP_SERVER) // ServerType::WS_SERVER to start a websocket server
    ->createServer($config)
    ->setServerConfig($config['additional'])
    ->registerCallbacks($config['callbacks'])
    ->getServerInstance()

// do things with the server instance like addProcess(),...
$server->start()
```
