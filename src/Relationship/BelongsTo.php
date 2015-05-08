<?php
namespace Clown\Relationship;

class BelongsTo extends Base
{
    protected function _parseForeignClass($metadata)
    {
        return $metadata['class'];
    }

    protected function _parseDefaultCondition($metadata)
    {
        $model = $this->_model;
        $foreignKey = $this->_parseForeignKey($metadata);
        $foreignClass = $this->_parseForeignClass($metadata);
        return array(
            'sql' => $foreignClass::$primaryKey . ' = ?',
            'value' => $model->$foreignKey
        );
    }

    protected function _parseRecord($metadata)
    {
        return $metadata['class']::first($metadata);
    }

    protected function _save()
    {
        if (empty($this->_records)) {
            return true;
        }

        foreach ($this->_records as $key => $record) {
            $metadata = $this->_parseMetadata($this->_metadata[$key]);
            if (($record instanceof $metadata['class']) && $record->isNewRecord()) {
                $this->_records[$key] = $metadata['class']::create(
                    $record->toArray()
                );
                $this->_model->$metadata['foreign_key'] = $this->_records[$key]->primary();
            }
        }

        return true;
    }

    public function set($name, $model)
    {
        $this->_records[$name] = $model;
    }

    public function destroy()
    {
    }
}