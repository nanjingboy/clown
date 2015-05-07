<?php
namespace Clown;

class Config extends Singleton
{
    private static $_config;

    private function _loadModel($class)
    {
        $modelPath = $this->get('model_path');
        if (!file_exists($modelPath)) {
            return false;
        }

        $classPath = $modelPath . DIRECTORY_SEPARATOR . $class . '.php';
        if (file_exists($classPath)) {
            require $classPath;
            return true;
        }

        return false;
    }

    public function init($configPath)
    {
        if (!file_exists($configPath)) {
            throw new ClownException("Can't load config from {$configPath}");
        }

        self::$_config = require($configPath);

        if (file_exists($this->get('model_path'))) {
            spl_autoload_register(array($this, '_loadModel'), true);
        }
    }

    public function get($key)
    {
        return !empty(self::$_config[$key]) ? self::$_config[$key] : null;
    }
}