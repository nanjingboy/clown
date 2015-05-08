<?php
namespace Clown;

use Clown\Relationship\HasOne;
use Clown\Relationship\HasMany;
use Clown\Relationship\BelongsTo;

class Relationship
{
    private $_hasMany = null;
    private $_hasOne = null;
    private $_belongsTo = null;

    private function _getInstance($name)
    {
        if ($this->_hasMany->exists($name)) {
            return $this->_hasMany;
        }

        if ($this->_hasOne->exists($name)) {
            return $this->_hasOne;
        }

        if ($this->_belongsTo->exists($name)) {
            return $this->_belongsTo;
        }

        return null;
    }

    public function __construct($model)
    {
        $this->_hasMany = new HasMany($model);
        $this->_hasOne = new HasOne($model);
        $this->_belongsTo = new BelongsTo($model);
    }

    public function get($name)
    {
        $instance = $this->_getInstance($name);
        return ($instance === null ? false : $instance->get($name));
    }

    public function getMetadata($name)
    {
        $instance = $this->_getInstance($name);
        return ($instance === null ? null : $instance->getMetadata($name));
    }

    public function exists($name)
    {
        if ($this->_hasMany->exists($name)) {
            return true;
        }

        if ($this->_hasOne->exists($name)) {
            return true;
        }

        if ($this->_belongsTo->exists($name)) {
            return true;
        }

        return false;
    }

    public function set($name, $model)
    {
        $instance = $this->_getInstance($name);
        return ($instance === null ? false : $instance->set($name, $model));
    }

    public function __call($method, $arguments)
    {
        if (!in_array($method, array('create', 'update', 'destroy'))) {
            throw new UndefinedMethodException($method, get_class($this));
        }

        $closure = $arguments[0];
        $relationshipsCount = count($this->_hasMany->getMetadata()) +
            count($this->_hasOne->getMetadata()) +
            count($this->_belongsTo->getMetadata());
        if ($relationshipsCount <= 0) {
            return $closure();
        }

        return Connection::instance()->transaction(function() use($method, $closure) {
            if ($this->_belongsTo->$method() === false) {
                return false;
            }

            if ($method !== 'destroy') {
                if ($closure() === false) {
                    return false;
                }
            }

            if ($this->_hasMany->$method() === false) {
                return false;
            }

            if ($this->_hasOne->$method() === false) {
                return false;
            }

            if ($method === 'destroy') {
                if ($closure() === false) {
                    return false;
                }
            }

            return true;
        });
    }
}