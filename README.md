[![Actions Status](https://github.com/ody-dev/ody-http-server/workflows/Build%20and%20test/badge.svg)](https://github.com/ody-dev/ody-http-server/actions)
[![License](https://poser.pugx.org/ody/core/license)](https://packagist.org/packages/ody/core)

# Ody Server

Ody Server is a package for the Ody PHP framework that provides high-performance HTTP server capabilities powered by
Swoole. It offers easy server management with command-line tools, hot-reloading support, and administrative features.

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Basic Usage](#basic-usage)
- [Configuration](#configuration)
- [Server Management](#server-management)
- [Server Events](#server-events)
- [Admin Server](#admin-server)
- [API Reference](#api-reference)

## Introduction

Ody Server simplifies the process of creating and managing Swoole-based servers. It provides:

- Command-line tools for managing server processes
- HTTP, WebSocket, and TCP server support
- Hot reloading capabilities for development
- Server state management
- Administrative API interface for monitoring and management

## Installation

```bash
composer require ody-dev/server
```

## Basic Usage

### Starting a Server

To start a server, use the `server:start` command:

```bash
php ody server:start
```

Options:

- `--daemonize` or `-d`: Run the server in the background
- `--watch` or `-w`: Enable file watching for hot reloading

### Stopping a Server

To stop a running server:

```bash
php ody server:stop
```

### Reloading a Server

To reload workers without stopping the server:

```bash
php ody server:reload
```

## Configuration

Server configuration should be defined in your application's `config/server.php` file:

```php
return [
    // Server host
    'host' => env('SERVER_HOST', '127.0.0.1'),
    
    // Server port
    'port' => env('SERVER_PORT', 9501),
    
    // Server mode (SWOOLE_BASE or SWOOLE_PROCESS)
    'mode' => SWOOLE_PROCESS,
    
    // Socket type
    'sock_type' => SWOOLE_SOCK_TCP,
    
    // SSL configuration
    'ssl' => [
        'ssl_cert_file' => env('SSL_CERT_FILE', null),
        'ssl_key_file' => env('SSL_KEY_FILE', null),
    ],
    
    // Additional Swoole server settings
    'additional' => [
        'worker_num' => env('SERVER_WORKER_NUM', 4),
        'task_worker_num' => env('SERVER_TASK_WORKER_NUM', 2),
        'reactor_num' => env('SERVER_REACTOR_NUM', 2),
        'max_request' => 1000,
        'buffer_output_size' => 2 * 1024 * 1024,
        'admin_server' => '127.0.0.1:9506',
    ],
    
    // Server event callbacks
    'callbacks' => [
        'start' => [\Ody\Server\ServerCallbacks::class, 'onStart'],
        'workerStart' => [\Ody\Server\ServerCallbacks::class, 'onWorkerStart'],
        'managerStart' => [\Ody\Server\ServerCallbacks::class, 'onManagerStart'],
        'managerStop' => [\Ody\Server\ServerCallbacks::class, 'onManagerStop'],
        'request' => [\Ody\Server\ServerCallbacks::class, 'onRequest'],
        'workerError' => [\Ody\Server\ServerCallbacks::class, 'onWorkerError'],
        // Add additional callbacks as needed
    ],
    
    // File paths to watch for hot reloading (when --watch is enabled)
    'watch_paths' => [
        'app/',
        'config/',
        'routes/',
    ],
];
```

## Server Management

The `ServerManager` class provides an interface for creating and managing Swoole servers.

```php
use Ody\Server\ServerManager;
use Ody\Server\ServerType;
use Ody\Server\State\HttpServerState;

// Initialize the server manager
$serverManager = ServerManager::init(ServerType::HTTP_SERVER)
    ->createServer($config)
    ->setServerConfig($additionalConfig)
    ->registerCallbacks($callbacks)
    ->daemonize($daemonize);

// Get the server instance
$server = $serverManager->getServerInstance();

// Start the server
Server::start($server);
```

## Server Events

The following server events can be registered in your configuration:

| Event              | Description                                             |
|--------------------|---------------------------------------------------------|
| `ON_START`         | Triggered when the server starts                        |
| `ON_WORKER_START`  | Triggered when a worker process starts                  |
| `ON_WORKER_STOP`   | Triggered when a worker process stops                   |
| `ON_WORKER_EXIT`   | Triggered when a worker process exits                   |
| `ON_WORKER_ERROR`  | Triggered when a worker process encounters an error     |
| `ON_PIPE_MESSAGE`  | Triggered when a message is sent through pipes          |
| `ON_REQUEST`       | Triggered when an HTTP request is received              |
| `ON_RECEIVE`       | Triggered when data is received                         |
| `ON_CONNECT`       | Triggered when a client connects                        |
| `ON_DISCONNECT`    | Triggered when a client disconnects                     |
| `ON_OPEN`          | Triggered when a WebSocket connection is opened         |
| `ON_MESSAGE`       | Triggered when a WebSocket message is received          |
| `ON_CLOSE`         | Triggered when a connection is closed                   |
| `ON_TASK`          | Triggered when a task is received                       |
| `ON_FINISH`        | Triggered when a task is finished                       |
| `ON_SHUTDOWN`      | Triggered when the server shuts down                    |
| `ON_PACKET`        | Triggered when a UDP packet is received                 |
| `ON_MANAGER_START` | Triggered when the manager process starts               |
| `ON_MANAGER_STOP`  | Triggered when the manager process stops                |
| `ON_BEFORE_START`  | Triggered before the server starts (not a Swoole event) |

## Server State

The `HttpServerState` class manages the state of running server processes, allowing for tracking and management of
processes.

```php
$serverState = HttpServerState::getInstance();

// Check if the server is running
if ($serverState->httpServerIsRunning()) {
    // Server is running
}

// Get process IDs
$masterPid = $serverState->getMasterProcessId();
$managerPid = $serverState->getManagerProcessId();
$workerPids = $serverState->getWorkerProcessIds();

// Kill processes
$serverState->killProcesses([
    $masterPid,
    $managerPid,
    // ...worker PIDs
]);

// Reload processes
$serverState->reloadProcesses([
    $masterPid,
    $managerPid,
    // ...worker PIDs
]);

// Clear process IDs
$serverState->clearProcessIds();
```

## Admin Server

!! Use with caution, very experimental and likely to break !!

The `AdminServer` class provides an administrative interface for monitoring and managing Swoole servers. It exposes a
RESTful API for interacting with the server processes.

To enable the Admin server, add the following to your server configuration:

```php
'additional' => [
    // ...
    'admin_server' => '127.0.0.1:9502',
    // ...
],
```

You can access a web-based dashboard at `http://your-admin-server:port/dashboard`.

### Authentication

The admin server supports authentication via the `admin_server` URI:

```
username:password@host:port
```

When authenticated, an access token is generated as `sha1(username . password)` and must be provided in the
`X-ADMIN-SERVER-ACCESS-TOKEN` HTTP header.

### API Endpoints

The admin server exposes a RESTful API at the `/api` endpoint. The URL structure is:

```
/api/COMMAND/PROCESS
```

Where:

- `COMMAND` is one of the registered command names
- `PROCESS` identifies the target process (e.g., "master", "worker-0", "all")

#### Available Commands

| Command                   | Description                    | HTTP Method |
|---------------------------|--------------------------------|-------------|
| `server_reload`           | Reload worker processes        | POST        |
| `server_shutdown`         | Shut down the server           | POST        |
| `server_stats`            | Get server statistics          | GET         |
| `server_setting`          | Get server configuration       | GET         |
| `coroutine_stats`         | Get coroutine statistics       | GET         |
| `coroutine_list`          | Get active coroutines          | GET         |
| `coroutine_bt`            | Get coroutine backtrace        | POST        |
| `get_version_info`        | Get version information        | GET         |
| `get_worker_info`         | Get worker process information | GET         |
| `get_timer_list`          | Get active timers              | GET         |
| `get_server_memory_usage` | Get memory usage               | GET         |
| `get_server_cpu_usage`    | Get CPU usage                  | GET         |
| `close_session`           | Close a client connection      | POST        |
| `get_client_info`         | Get client information         | GET         |

#### Example Requests

Retrieve server statistics:

```
GET /api/server_stats/master
```

Reload the server:

```
POST /api/server_reload/master
```

Get coroutine list from all worker processes:

```
GET /api/coroutine_list/all_worker
```

Close a client connection:

```
POST /api/close_session/worker-0
{
  "session_id": 1
}
```

## API Reference

### Server Types

The `ServerType` class provides constants for different server types:

- `HTTP_SERVER`: Swoole HTTP server (`\Swoole\Http\Server`)
- `WS_SERVER`: Swoole WebSocket server (`\Swoole\WebSocket\Server`)
- `TCP_SERVER`: Swoole TCP server (`\Swoole\Server`)

### Server Events

The `ServerEvent` class provides constants for all supported server events:

- `ON_START`: Server start event
- `ON_WORKER_START`: Worker process start event
- `ON_WORKER_STOP`: Worker process stop event
- `ON_WORKER_EXIT`: Worker process exit event
- `ON_WORKER_ERROR`: Worker process error event
- `ON_PIPE_MESSAGE`: Pipe message event
- `ON_REQUEST`: HTTP request event
- `ON_RECEIVE`: Data receive event
- `ON_CONNECT`: Client connect event
- `ON_DISCONNECT`: Client disconnect event
- `ON_OPEN`: WebSocket open event
- `ON_MESSAGE`: WebSocket message event
- `ON_CLOSE`: Connection close event
- `ON_TASK`: Task event
- `ON_FINISH`: Task finish event
- `ON_SHUTDOWN`: Server shutdown event
- `ON_PACKET`: UDP packet event
- `ON_MANAGER_START`: Manager start event
- `ON_MANAGER_STOP`: Manager stop event
- `ON_BEFORE_START`: Before server start event (not a Swoole event)

### ServerManager Class

Methods:

- `init(string $serverType): static` - Initialize the server manager with a server type
- `createServer(?array $config): static` - Create a new server instance with the given configuration
- `setServerConfig(array $config): static` - Set additional server configuration
- `getServerInstance(): HttpServer|WsServer` - Get the server instance
- `registerCallbacks(array $callbacks): static` - Register event callbacks
- `setWatcher(int $enableWatcher, array $paths, object $serverState): static` - Enable file watching for hot reloading
- `daemonize(bool $daemonize): static` - Set the server to run in the background
- `setLogger(LoggerInterface $logger): self` - Set the logger instance
- `setConfig(Config $config): self` - Set the configuration instance
- `start(): void` - Start the server

### ServerState Class

Methods:

- `getInstance(): self` - Get the singleton instance
- `getInformation(): array` - Get the state information
- `setManagerProcessId(?int $id): void` - Set the manager process ID
- `setMasterProcessId(?int $id): void` - Set the master process ID
- `setWatcherProcessId(?int $id): void` - Set the watcher process ID
- `setWorkerProcessIds(array $ids): void` - Set the worker process IDs
- `getManagerProcessId(): int|null` - Get the manager process ID
- `getMasterProcessId(): int|null` - Get the master process ID
- `getWatcherProcessId(): int|null` - Get the watcher process ID
- `getWorkerProcessIds(): array` - Get the worker process IDs
- `clearProcessIds(): void` - Clear all process IDs
- `reloadProcesses(array $processIds): void` - Reload the specified processes
- `killProcesses(array $processIds): void` - Kill the specified processes

### HttpServerState Class

Methods:

- `getInstance(): self` - Get the singleton instance
- `httpServerIsRunning(): bool` - Check if the HTTP server is running

### AdminServer Class

Methods:

- `init(Server $server): void` - Initialize the admin server
- `getAccessToken(): string` - Get the access token
- `start(Server $server): void` - Start the admin server

### ServerCallbacks Class

Static methods:

- `onStart(SwServer $server): void` - Handle server start event
- `onRequest(SwRequest $request, SwResponse $response): void` - Handle HTTP request event
- `onWorkerStart(SwServer $server, int $workerId): void` - Handle worker start event
- `onManagerStart(SwServer $server)` - Handle manager start event
- `onManagerStop(SwServer $server)` - Handle manager stop event
- `onReceive(SwServer $server, int $fd, int $reactorId, string $data)` - Handle data receive event
- `onWorkerError(SwServer $server, int $workerId, int $workerPid, int $exitCode, int $signal): void` - Handle worker
  error event