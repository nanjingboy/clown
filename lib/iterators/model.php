<?php
namespace Clown\Iterators;

use ArrayIterator;
use Clown\Iterators\Record as RecordIterator;

Class Model extends ArrayIterator
{
    private $_modelName;
    private $_toArray;
    private $_withRelationships;

    private function _parseRecord($record)
    {
        $modelName = $this->_modelName;
        $record = $modelName::parseAttributes($record);
        if ($this->_toArray === true) {
            if ($this->_withRelationships === true) {
                return new RecordIterator($record, $modelName);
            }
            return $record;
        }

        $newRecord = false;
        if (isset($record[$modelName::NEW_RECORD_KEY])) {
            $newRecord = $record[$modelName::NEW_RECORD_KEY];
        }
        return new $modelName($record, $newRecord, false);
    }

    public function __construct($records, $modelName, $toArray = false, $withRelationships = false)
    {
        $this->_modelName = $modelName;
        $this->_toArray = $toArray;
        $this->_withRelationships = $withRelationships;
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
        $record = $model->toArray();
        $record[$model::NEW_RECORD_KEY] = $model->isNewRecord();
        parent::offsetSet($key, $record);
    }
}