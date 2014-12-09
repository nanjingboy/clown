<?php
namespace Clown;

use Closure;

class Callback
{
    private static $_TYPES = array(
        'beforeSave',
        'beforeInsert',
        'beforeUpdate',
        'beforeDelete',
        'beforeRestore',
        'beforeDestroy',
        'afterSave',
        'afterInsert',
        'afterUpdate',
        'afterDelete',
        'afterRestore',
        'afterDestroy'
    );

    private $_model;
    private $_registeredCallbacks;

    public function __construct($model)
    {
        $modelName = get_class($model);
        $modelMethods = get_class_methods($modelName);
        $modelProperties = get_class_vars($modelName);

        $this->_model = $model;
        $this->_registeredCallbacks = array();

        foreach (self::$_TYPES as $type) {
            if (!empty($modelProperties[$type])) {
                $callbacks = $modelProperties[$type];
                if (is_array($callbacks)) {
                    foreach ($callbacks as $callback) {
                        $this->register($type, $callback);
                    }
                } else {
                    $this->register($type, $callbacks);
                }
            } else if (in_array($type, $modelMethods)) {
                $this->register($type, $type);
            }
        }
    }

    public function register($type, $callback = null, $prepend = false)
    {
        if (!in_array($type, self::$_TYPES)) {
            throw new ClownException("Invalid callback: {$type}");
        }

        if (!isset($this->_registeredCallbacks[$type])) {
            $this->_registeredCallbacks[$type] = array();
        }

        if (in_array($callback, $this->_registeredCallbacks[$type])) {
            throw new ClownException("Callback {$type}:{$callback} has already binded.");
        }

        $callback = ($callback == null ? $type : $callback);
        if (!($callback instanceof Closure)) {
            $methods = get_class_methods(get_class($this->_model));
            if (!in_array($callback, $methods)) {
                throw new ClownException(
                    "Unknown method for callback: {$type}" .
                    (is_string($callback) ? ": {$callback}" : "")
                );
            }
        }

        if ($prepend) {
            array_unshift($this->_registeredCallbacks[$type], $callback);
        } else {
            array_push($this->_registeredCallbacks[$type], $callback);
        }
    }

    public function call($type)
    {
        if (array_key_exists($type, $this->_registeredCallbacks)) {
            $callbacks = $this->_registeredCallbacks[$type];
        } else {
            $callbacks = array();
        }

        if (preg_match('/^(before|after)(Update|Insert)$/', $type)) {
            $saveType = str_replace(array('Update', 'Insert'), 'Save', $type);
            if (!isset($this->_registeredCallbacks[$saveType])) {
                $this->_registeredCallbacks[$saveType] = array();
            }
            $callbacks = array_merge($callbacks, $this->_registeredCallbacks[$saveType]);
        }

        foreach ($callbacks as $callback) {
            $result = true;
            if ($callback instanceof Closure) {
                $result = $callback($this->_model);
            } else {
                $result = $this->_model->$callback();
            }

            if ($result === false && preg_match('/^before[A-Z][a-z]+$/', $type)) {
                return false;
            }
        }

        return true;
    }

    public function getCallbacks($type)
    {
        return (isset($this->_registeredCallbacks[$type]) ? $this->_registeredCallbacks[$type] : null);
    }
}