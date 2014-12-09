<?php
namespace Clown\Iterators;

use ArrayIterator;
use Clown\Helper;
use Clown\RelationshipBindException;
use Clown\Iterators\Model as ModelIterator;
use Clown\Traits\Relationship\Metadata as MetadataTrait;

abstract class Relationship extends ArrayIterator
{
    use MetadataTrait;

    protected $_recordIterator;

    abstract protected function _setRecord($object, $metadata);
    abstract protected function _updateRecordIterator();

    protected function _resetRecordIterator()
    {
        $this->_recordIterator = new ArrayIterator(array());
    }

    protected function _getConditions($metadata)
    {
        $metadata['foreignKeyData'] = $this->_model->toArray();
        return $this->_parseConditions($metadata);
    }

    protected function _getRecord($metadata)
    {
        $recordKey = $this->_parseKey($this->key());
        if ($this->_recordIterator->offsetExists($recordKey)) {
            return $this->_recordIterator[$recordKey];
        }

        if ($this->_model->isNewRecord()) {
            if ($metadata['type'] === 'hasMany') {
                $record = new ModelIterator(array(), $metadata['class'], false);
            } else {
                $record = null;
            }
        } else {
            $metadata['foreignKeyData'] = $this->_model->toArray();
            $metadata['toArray'] = false;
            $metadata['withRelationships'] = false;
            $record = $this->_parseRecord($metadata);
        }

        if ($record !== null) {
            $this->_recordIterator[$recordKey] = $record;
        }

        return $record;
    }

    protected function _getMetadata($key = null)
    {
        if ($key === null) {
            $metadata = array();
            $relationships = parent::getArrayCopy();
            foreach ($relationships as $relationship) {
                array_push($metadata, $this->_parseMetadata($relationship));
            }
            return $metadata;
        }

        if ($this->offsetExists($key)) {
            return $this->_parseMetadata(parent::offsetGet($this->_parseKey($key)));
        }
    }

    public function __construct($model)
    {
        $this->_model = $model;
        $this->_resetRecordIterator();
        $type = lcfirst(array_pop(explode('\\', get_called_class())));
        $relationships = array();
        foreach ($model::$$type as $relationship) {
            $relationship['type'] = $type;
            $relationships[$this->_parseKey($relationship[0])] = $relationship;
        }
        parent::__construct($relationships);
    }

    public function getArrayCopy()
    {
        $relationships = parent::getArrayCopy();
        foreach ($relationships as $key => $relationship) {
            $relationships[$key] = $this->_getRecord($relationship);
        }
        return $relationships;
    }

    public function current()
    {
        return $this->_getRecord(parent::current());
    }

    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->_getRecord($this->_getMetadata($key));
        }

        return false;
    }

    public function offsetSet($key, $object)
    {
        $key = $this->_parseKey($key);
        if ($object === null) {
            $this->_recordIterator[$key] = null;
        } else {
            $metadata = $this->_getMetadata($key);
            if ($metadata === false) {
                return false;
            }

            if ((get_class($object) !== $metadata['class'])) {
                throw new RelationshipBindException(
                    $metadata['class'], get_class($this->_model)
                );
            }

            $this->_recordIterator[$key] = $this->_setRecord(
                $object, $metadata
            );
        }
    }

    public function offsetExists($key)
    {
        return parent::offsetExists($this->_parseKey($key));
    }

    public function create()
    {
        return $this->_updateRecordIterator();
    }

    public function update()
    {
        return $this->_updateRecordIterator();
    }

    public function destroy()
    {
    }
}
