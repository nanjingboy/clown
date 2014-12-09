<?php
namespace Clown\Iterators;

use Clown\Iterators\Model as ModelIterator;
use Clown\Traits\Relationship\Operation as OperationTrait;

class HasMany extends Relationship
{
    use OperationTrait;

    protected function _setRecord($object, $metadata)
    {
        $object->$metadata['foreignKey'] = $this->_model->id;
        $record = $this->_getRecord($metadata);
        if ($record === false) {
            $record = new ModelIterator(array(), $metadata['class'], false);
        }

        $record->append($object);
        return $record;
    }

    protected function _updateRecordIterator()
    {
        if ($this->_recordIterator->count() <= 0) {
            return true;
        }

        foreach ($this->_recordIterator as $key => $records) {
            $metadata = $this->_getMetadata($key);
            $modelIterator = new ModelIterator(array(), $metadata['class'], false);
            foreach ($records as $record) {
                $record->$metadata['foreignKey'] = $this->_model->id;
                if ($record->isNewRecord()) {
                    $modelIterator->append($record::create($record->toArray()));
                } else {
                    $changedAttributes = $record->changedAttributes;
                    if (!empty($changedAttributes)) {
                        $record::updateAll($changedAttributes, array('id' => $record->idWas));
                        $modelIterator->append($record::find($record->idWas));
                    } else {
                        $modelIterator->append($record);
                    }

                }
            }
            $this->_recordIterator[$key] = $modelIterator;
        }
    }
}