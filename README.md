## HTTP server
```
composer require ody/http-server
```


```php
/**
 * Returns an $app instance, in theory this could be anything 
 * as long as it handles psr7 requests/responses. You could for 
 * example plug a Slim framework instance in here.
 */
$kernel = Kernel::init();

(new Http())->createServer(
    $kernel
)->start(),
```
