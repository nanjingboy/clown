<?php
namespace Clown;

use Exception;

class Schema
{
    public static function parseMethod($method)
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
        $methodParams = static::parseMethod($method);
        if (count($argumnets) <= 0) {
            throw new MissingArgumentException($method, __CLASS__);
        }

        $className = "\\Clown\\{$methodParams['class']}";
        $class = $className::instance();
        if (!method_exists($class, $methodParams['method'])) {
            throw new UndefinedMethodException($method, __CLASS__);
        }

        try {
            return call_user_method_array(
                $methodParams['method'],
                $class,
                $argumnets
            );
        } catch (Exception $e) {
            throw new ClownException($e);
        }
    }
}