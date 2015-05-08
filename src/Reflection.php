<?php
namespace Clown;

use ReflectionClass;
use ReflectionMethod;

class Reflection
{
    private static $_reflectionClasses = array();

    public static function getReflectionClass($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }
        if (!isset(self::$_reflectionClasses[$className])) {
            self::$_reflectionClasses[$className] = new ReflectionClass($className);
        }
        return self::$_reflectionClasses[$className];
    }

    public static function invokeMethod($class, $methodName, $arguments = array())
    {
        $method = new ReflectionMethod($class, $methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($class, $arguments);
    }
}