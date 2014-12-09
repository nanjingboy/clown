<?php
namespace Clown\Iterators;

use Clown\UndefinedMethodException;

class BelongsTo extends Relationship
{
    protected function _setRecord($object, $metadata)
    {
        $this->_model->$metadata['foreignKey'] = $object->id;
        return $object;
    }

    protected function _updateRecordIterator()
    {
        if ($this->_recordIterator->count() <= 0) {
            return true;
        }

        foreach ($this->_recordIterator as $key => $record) {
            if ($record->isNewRecord()) {
                $this->offsetSet($key, $record::create($record->toArray()));
            }
        }
    }
}