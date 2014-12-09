<?php
namespace Clown;

class Config
{
    private static $_config;

    public static function init($configPath)
    {
        if (file_exists($configPath) === false) {
            throw new ClownException("Can't load config from {$configPath}");
        }

        self::$_config = require($configPath);
    }

    public static function get($key)
    {
        return self::$_config[$key];
    }
}