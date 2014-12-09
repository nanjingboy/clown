<?php
namespace Clown\Iterators;

use ArrayIterator;
use Clown\Helper;
use Clown\Traits\Relationship\Metadata as MetadataTrait;

class Record extends ArrayIterator
{
    use MetadataTrait;

    private $_relationshipRecords;

    private function _getRelationship($key)
    {
        $model = $this->_model;
        $key = $this->_parseKey($key);
        foreach (array('hasMany', 'hasOne', 'belongsTo') as $type) {
            foreach ($model::$$type as $relationship) {
                if ($key === $this->_parseKey($relationship[0])) {
                    $relationship['type'] = $type;
                    return $relationship;
                }
            }
        }
        return null;
    }

    public function __construct($attributes, $modelName)
    {
        $this->_model = $modelName;
        $this->_relationshipRecords = array();
        parent::__construct($attributes);
    }

    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return parent::offsetGet($key);
        }

        $recordKey = $this->_parseKey($key);
        if (array_key_exists($recordKey, $this->_relationshipRecords)) {
            return $this->_relationshipRecords[$recordKey];
        }

        $relationship = $this->_getRelationship($key);
        if ($relationship === null) {
            return null;
        }

        $metadata = $this->_parseMetadata($relationship);
        if ($metadata['type'] === 'belongsTo') {
            $metadata['foreignKeyData'] = array(
                $metadata['foreignKey'] => parent::offsetGet($metadata['foreignKey'])
            );
        } else {
            $metadata['foreignKeyData'] = array(
                'id' => parent::offsetGet('id')
            );
        }

        $metadata['toArray'] = true;
        $metadata['withRelationships'] = true;
        $record = $this->_parseRecord($metadata);
        $this->_relationshipRecords[$recordKey] = $record;

        return $record;
    }
}