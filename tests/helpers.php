<?php

use Ody\Server\Tests\Config;

if (! function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return Config::getInstance()->get($key , $default);
    }
}

if (! function_exists('configPath')) {
    function configPath(string $path = null): string
    {
        return base_path("config/$path");
    }
}

if (! function_exists('basePath')) {
    function base_path(string $path = null): string
    {
        /** @psalm-suppress UndefinedConstant */
        return realpath(PROJECT_PATH) . "/$path";
    }
}

if (! function_exists('storagePath')) {
    function storagePath(string $path = null): string
    {
        return base_path("storage/$path");
    }
}

if (!function_exists('dd'))
{
    function dd()
    {
        array_map(function ($content) {
            echo "<pre>";
            var_dump($content);
            echo "</pre>";
            echo "<hr>";
        }, func_get_args());

        die;
    }
}