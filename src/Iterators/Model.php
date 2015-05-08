<?php
namespace Clown\Iterators;

use ArrayIterator;
use Clown\ClownException;

Class Model extends ArrayIterator
{
    private $_modelName;

    private function _parseRecord($record)
    {
        $isNewRecord = false;
        $modelName = $this->_modelName;
        if (isset($record[$modelName::NEW_RECORD_KEY])) {
            $isNewRecord = $record[$modelName::NEW_RECORD_KEY];
        }
        return new $modelName($record, $isNewRecord);
    }

    public function __construct($records, $modelName)
    {
        $this->_modelName = $modelName;
        parent::__construct($records);
    }

    public function getArrayCopy()
    {
        $records = parent::getArrayCopy();
        foreach ($records as $key => $record) {
            $records[$key] = $this->_parseRecord($record);
        }

        return $records;
    }

    public function current()
    {
        return $this->_parseRecord(parent::current());
    }

    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->_parseRecord(parent::offsetGet($key));
        }

        return null;
    }

    public function offsetSet($key, $model)
    {
        if ($model instanceof $this->_modelName) {
            $record = $model->toArray();
            $record[$model::NEW_RECORD_KEY] = $model->isNewRecord();
            parent::offsetSet($key, $record);
        } else {
            throw new ClownException("Can't convert to {$this->_modelName}");
        }
    }

    public function first()
    {
        return $this->offsetGet(0);
    }

    public function last()
    {
        return $this->offsetGet(count($this) - 1);
    }

    public function toArray()
    {
        $records = $this->getArrayCopy();
        foreach ($records as $key => $record) {
            $records[$key] = $record->toArray();
        }

        return $records;
    }
}