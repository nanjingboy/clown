<?php
namespace Clown;

use Exception;

class Schema
{
    private static function _parseMethod($method)
    {
        $matches = array();
        preg_match_all('/^([a-z]+)([A-Z][a-z]+)$/', $method, $matches);

        if (isset($matches[1][0]) && isset($matches[2][0])) {
            return array('class' => $matches[2][0], 'method' => $matches[1][0]);
        }

        throw new UndefinedMethodException($method, __CLASS__);
    }

    public static function __callStatic($method, $argumnets)
    {
        $methodParams = self::_parseMethod($method);
        $className = "\\Clown\\{$methodParams['class']}";
        $class = $className::instance();

        if (!method_exists($class, $methodParams['method'])) {
            throw new UndefinedMethodException($method, __CLASS__);
        }

        try {
            return call_user_func_array(array($class, $methodParams['method']), $argumnets);
        } catch (Exception $exception) {
            throw new ClownException($exception);
        }
    }
}