<?php
namespace Clown;

use Clown\Iterators\HasMany as HasManyIterator;
use Clown\Iterators\HasOne as HasOneIterator;
use Clown\Iterators\BelongsTo as BelongsToIterator;

class Relationship
{
    private $_hasManyIterator;
    private $_hasOneIterator;
    private $_belongsToIterator;

    public function __construct($model)
    {
        $this->_hasManyIterator = new HasManyIterator($model);
        $this->_hasOneIterator = new HasOneIterator($model);
        $this->_belongsToIterator = new BelongsToIterator($model);
    }

    public function getIterator($name)
    {
        if ($this->_hasManyIterator->offsetExists($name)) {
            return $this->_hasManyIterator;
        }

        if ($this->_hasOneIterator->offsetExists($name)) {
            return $this->_hasOneIterator;
        }

        if ($this->_belongsToIterator->offsetExists($name)) {
            return $this->_belongsToIterator;
        }

        return null;
    }

    public function get($name)
    {
        $iterator = $this->getIterator($name);
        return ($iterator === null ? false : $iterator[$name]);
    }

    public function set($name, $value)
    {
        $iterator = $this->getIterator($name);
        return ($iterator === null ? false : $iterator->offsetSet($name, $value));
    }

    public function __call($method, $arguments)
    {
        if (!in_array($method, array('create', 'update', 'destroy'))) {
            throw new UndefinedMethodException($method, get_class($this));
        }

        $closure = $arguments[0];
        $relationshipCount = $this->_hasManyIterator->count() +
            $this->_hasOneIterator->count() +
            $this->_belongsToIterator->count();

        if ($relationshipCount <= 0) {
            return $closure();
        }

        return Db::instance()->translation(function() use($method, $closure) {
            if ($this->_belongsToIterator->$method() === false) {
                return false;
            }

            if ($closure() === false) {
                return false;
            }

            if ($this->_hasManyIterator->$method() === false) {
                return false;
            }

            if ($this->_hasOneIterator->$method() === false) {
                return false;
            }

            return true;
        });
    }
}