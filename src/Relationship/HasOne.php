<?php
namespace Clown\Relationship;

class HasOne extends Base
{
    protected function _parseForeignClass($metadata)
    {
        return get_class($this->_model);
    }

    protected function _parseDefaultCondition($metadata)
    {
        return array(
            'sql' => $this->_parseForeignKey($metadata) . ' = ?',
            'value' => $this->_model->primary()
        );
    }

    protected function _parseRecord($metadata)
    {
        return $metadata['class']::first($metadata);
    }

    protected function _save()
    {
        if (empty($this->_metadata)) {
            return true;
        }

        foreach ($this->_metadata as $key => $metadata) {
            $metadata = $this->_parseMetadata($metadata);
            if (!isset($this->_records[$key]) || !($this->_records[$key] instanceof $metadata['class'])) {
                continue;
            }

            $metadata['class']::updateAll(
                array($metadata['foreign_key'] => null),
                array($metadata['foreign_key'] => $this->_model->primaryWas())
            );
            $record = $this->_records[$key];
            $record->$metadata['foreign_key'] = $this->_model->primary();
            if ($record->isNewRecord()) {
                $this->_records[$key] = $metadata['class']::create(
                    $record->toArray()
                );
            } else {
                $metadata['class']::updateAll(
                    array($metadata['foreign_key'] => $this->_model->primary()),
                    array($metadata['class']::$primaryKey => $record->primaryWas())
                );
                $this->_records[$key] = $metadata['class']::first(
                    array(
                        'conditions' => array(
                            $metadata['class']::$primaryKey => $record->primaryWas()
                        )
                    )
                );
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
        if (empty($this->_metadata)) {
            return true;
        }

        foreach ($this->_metadata as $metadata) {
            $metadata = $this->_parseMetadata($metadata);
            if (!empty($metadata['dependent']) && $metadata['dependent'] === 'destroy') {
                $metadata['class']::destroyAll(
                    array($metadata['foreign_key'] => $this->_model->primaryWas())
                );
            } else {
                $metadata['class']::updateAll(
                    array($metadata['foreign_key'] => null),
                    array($metadata['foreign_key'] => $this->_model->primaryWas())
                );
            }
        }

        return true;
    }
}