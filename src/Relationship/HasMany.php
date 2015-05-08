<?php
namespace Clown\Relationship;

use Clown\Iterators\Model as ModelIterator;

class HasMany extends Base
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
        return $metadata['class']::find($metadata);
    }

    protected function _save()
    {
        if (empty($this->_metadata)) {
            return true;
        }

        foreach ($this->_metadata as $key => $metadata) {
            $metadata = $this->_parseMetadata($metadata);
            if ($this->_model->primary() !== $this->_model->primaryWas()) {
                $metadata['class']::updateAll(
                    array($metadata['foreign_key'] => $this->_model->primary()),
                    array($metadata['foreign_key'] => $this->_model->primaryWas())
                );
            }

            if (!isset($this->_records[$key]) || !($this->_records[$key] instanceof ModelIterator)) {
                continue;
            }

            $modelIterator = new ModelIterator(array(), $metadata['class']);
            foreach ($this->_records[$key] as $record) {
                $record->$metadata['foreign_key'] = $this->_model->primary();
                if ($record->isNewRecord()) {
                    $modelIterator->append(
                        $metadata['class']::create($record->toArray())
                    );
                } else {
                    $metadata['class']::updateAll(
                        array($metadata['foreign_key'] => $this->_model->primary()),
                        array($metadata['class']::$primaryKey => $record->primaryWas())
                    );
                    $modelIterator->append(
                        $metadata['class']::first(
                            array(
                                'conditions' => array(
                                    $metadata['class']::$primaryKey => $record->primaryWas()
                                )
                            )
                        )
                    );
                }
            }

            $this->_records[$key] = $modelIterator;
        }

        return true;
    }

    public function set($name, $model)
    {
        if ($model instanceof ModelIterator) {
            $this->_records[$name] = $model;
        } else {
            if (!isset($this->_records[$name]) || !($this->_records[$name] instanceof ModelIterator)) {
                $this->_records[$name] = new ModelIterator(
                    array(), $this->_metadata[$name]['class']
                );
            }

            $this->_records[$name]->append($model);
        }
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