<?php
namespace Clown\Iterators;

use Clown\Traits\Relationship\Operation as OperationTrait;

class HasOne extends Relationship
{
    use OperationTrait;

    protected function _setRecord($object, $metadata)
    {
        $object->$metadata['foreignKey'] = $this->_model->id;
        return $object;
    }

    protected function _updateRecordIterator()
    {
        if ($this->_recordIterator->count() <= 0) {
            return true;
        }

        foreach ($this->_recordIterator as $key => $record) {
            $metadata = $this->_getMetadata($key);
            $record->$metadata['foreignKey'] = $this->_model->id;
            if ($record->isNewRecord()) {
                $this->offsetSet($key, $record::create($record->toArray()));
            } else {
                $changedAttributes = $record->changedAttributes;
                if (!empty($changedAttributes)) {
                    $record::updateAll($changedAttributes, array('id' => $record->idWas));
                    $this->offsetSet($key, $record::find($record->idWas));
                }
            }
        }
    }
}