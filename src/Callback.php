<?php
namespace Clown;

use Closure;

class Callback
{
    private static $_TYPES = array(
        'before_create',
        'before_update',
        'before_save',
        'before_destroy',
        'before_validation',
        'before_validation_on_create',
        'before_validation_on_update',
        'after_create',
        'after_update',
        'after_save',
        'after_destroy',
        'after_validation',
        'after_validation_on_create',
        'after_validation_on_update'
    );

    private $_model;
    private $_registeredCallbacks;

    private function _getCallbacks($type)
    {
        $_type = null;
        if (array_key_exists($type, $this->_registeredCallbacks)) {
            $callbacks = $this->_registeredCallbacks[$type];
        } else {
            $callbacks = array();
        }

        if (preg_match('/^(before_|after_)(create|update)$/', $type)) {
            $_type = str_replace(array('create', 'update'), 'save', $type);
        } else if (preg_match('/^(before_|after_)(validation_on_create|validation_on_update)$/', $type)) {
            $_type = str_replace(array('validation_on_create', 'validation_on_update'), 'validation', $type);
        }

        if ($_type === null) {
            return $callbacks;
        }

        if (!isset($this->_registeredCallbacks[$_type])) {
            $this->_registeredCallbacks[$_type] = array();
        }

        return array_merge($callbacks, $this->_registeredCallbacks[$_type]);
    }

    public function __construct($model)
    {
        $this->_model = $model;
        $this->_registeredCallbacks = array();

        $reflectionClass = Reflection::getReflectionClass($model);
        $properties = $reflectionClass->getStaticProperties();
        foreach (self::$_TYPES as $type) {
            if (!empty($properties[$type])) {
                $callbacks = $properties[$type];
                if (is_array($callbacks)) {
                    foreach ($callbacks as $callback) {
                        $this->register($type, $callback);
                    }
                } else {
                    $this->register($type, $callbacks);
                }
            } else if ($reflectionClass->hasMethod($type)) {
                $this->register($type, $type);
            }
        }
    }

    public function register($type, $callback, $prepend = false)
    {
        if (!in_array($type, self::$_TYPES)) {
            throw new CallbackException("Invalid callback: {$type}");
        }

        if (!isset($this->_registeredCallbacks[$type])) {
            $this->_registeredCallbacks[$type] = array();
        }

        if (in_array($callback, $this->_registeredCallbacks[$type])) {
            throw new CallbackException("Callback {$type}:{$callback} has already binded.");
        }

        if ($prepend) {
            array_unshift($this->_registeredCallbacks[$type], $callback);
        } else {
            array_push($this->_registeredCallbacks[$type], $callback);
        }
    }

    public function call($type)
    {
        $callbacks = $this->_getCallbacks($type);
        foreach ($callbacks as $callback) {
            $result = true;
            if ($callback instanceof Closure) {
                $result = $callback($this->_model);
            } else {
                $result = Reflection::invokeMethod(
                    $this->_model, $callback
                );
            }
            if ($result === false && strpos($type, 'before_') === 0) {
                return false;
            }
        }
        return true;
    }
}