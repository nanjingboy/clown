<?php
namespace Clown;

use Closure;
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

    public static function invokeMethod($class, $methodName, $arguments = array(), $closure = null)
    {
        $method = new ReflectionMethod($class, $methodName);
        if (is_callable($closure) && $closure($method) === false) {
            return;
        }

        $method->setAccessible(true);
        return $method->invokeArgs($class, $arguments);
    }

    public static function invokeStaticMethod($class, $methodName, $arguments = array())
    {
        return static::invokeMethod(
            $class, $methodName, $arguments,
            function($method) {
                return $method->isStatic();
            }
        );
    }

    public static function invokeInstanceMethod($class, $methodName, $arguments = array())
    {
        return static::invokeMethod(
            $class, $methodName, $arguments,
            function($method) {
                return $method->isStatic() === false;
            }
        );
    }
}