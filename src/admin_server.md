# AdminServer Class Documentation

## Overview

The `AdminServer` class provides administrative capabilities for Swoole servers by exposing a RESTful API interface for 
monitoring and managing server processes. It leverages Swoole's coroutine-based HTTP server to handle administrative 
requests without blocking the main server processes.

## Namespace

```php
namespace Ody\Server;
```

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `SIZE_OF_ZVAL` | 16 | Memory size of a PHP zval structure |
| `SIZE_OF_ZEND_STRING` | 32 | Memory size of a PHP zend_string structure |
| `SIZE_OF_ZEND_OBJECT` | 56 | Memory size of a PHP zend_object structure |
| `SIZE_OF_ZEND_ARRAY` | 56 | Memory size of a PHP zend_array structure |

## Static Properties

| Property | Type | Description |
|----------|------|-------------|
| `$map` | array | Maps process type names to Swoole command constants |
| `$allList` | array | List of supported "all" process type identifiers |
| `$postMethodList` | array | List of commands that require POST HTTP method |
| `$accessToken` | string | Security token for authentication |

## Methods

### Public Methods

#### `init(Server $server): void`

Initializes the `AdminServer` by registering command handlers with the Swoole server.

```php
AdminServer::init($server);
```

This method sets up various command handlers for:
- Server management (reload, shutdown)
- Coroutine introspection (stats, listings, backtraces)
- Server statistics and settings
- Connection management
- Process information
- Memory usage metrics
- Class and function introspection
- PHP runtime information

#### `getAccessToken(): string`

Returns the current access token used for authentication.

```php
$token = AdminServer::getAccessToken();
```

#### `start(Server $server): void`

Starts the admin HTTP server to handle administrative API requests.

```php
AdminServer::start($server);
```

- Parses the admin server URI configuration
- Sets up authentication if configured
- Creates a Coroutine HTTP server
- Registers API route handlers
- Stores the admin server in the main server object
- Starts listening for connections

### Command Handler Methods

The class provides numerous handler methods for different administrative commands:

- `handlerGetResources`: Lists resource handles in the process
- `handlerGetWorkerInfo`: Retrieves worker process information
- `handlerCloseSession`: Closes a specific client connection
- `handlerGetTimerList`: Lists active timers
- `handlerGetCoroutineList`: Lists active coroutines
- `handlerGetObjects`: Lists PHP objects in memory
- `handlerGetClassInfo`: Retrieves detailed information about a class
- `handlerGetFunctionInfo`: Retrieves detailed information about a function or method
- `handlerGetObjectByHandle`: Gets object information by handle
- `handlerGetVersionInfo`: Returns version information
- `handlerGetDefinedFunctions`: Lists defined functions
- `handlerGetDeclaredClasses`: Lists declared classes
- `handlerGetServerMemoryUsage`: Measures memory usage of server processes
- `handlerGetServerCpuUsage`: Measures CPU usage of server processes
- `handlerGetStaticPropertyValue`: Retrieves a class's static property value

### Private Helper Methods

The class also contains various private helper methods:

- `handlerMulti`: Handles batch processing of multiple commands
- `handlerGetAll`: Retrieves information from all processes
- Memory calculation methods (`getArrayMemorySize`, `getStringMemorySize`, `getObjectMemorySize`)
- Process detection methods (`haveMasterProcess`, `haveManagerProcess`)
- `json`: Formats response data as JSON

## API Endpoints

The admin server exposes a RESTful API at the `/api` endpoint. The URL structure is:

```
/api/COMMAND/PROCESS
```

Where:
- `COMMAND` is one of the registered command names
- `PROCESS` identifies the target process (e.g., "master", "worker-0", "all")

## Authentication

Authentication is configured through the admin server URI:

```
username:password@host:port
```

The access token is generated as `sha1(username . password)` and must be provided in the `X-ADMIN-SERVER-ACCESS-TOKEN` HTTP header.

## Examples

### Starting the Admin Server

```php
$server = new Swoole\Server('0.0.0.0', 9501);
$server->set([
    'worker_num' => 4,
    'task_worker_num' => 2,
    'admin_server' => 'admin:password@127.0.0.1:9506'
]);

AdminServer::init($server);
// ... other server configuration
$server->on('start', function($server) {
    AdminServer::start($server);
});

$server->start();
```

### API Usage Examples

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