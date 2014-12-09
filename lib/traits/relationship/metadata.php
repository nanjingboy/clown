<?php
namespace Clown\Traits\Relationship;

use Clown\Helper;

trait Metadata
{
    protected $_model;

    private function _parseKey($key)
    {
        return Helper::underscore($key);
    }

    private function _parseMetadata($relationship)
    {
        if (isset($relationship['class'])) {
            $class = $relationship['class'];
        } else {
            $class = Helper::singularize($relationship[0]);
        }
        $class = ltrim(Helper::camelize($class), '\\');
        if (strpos($class, '\\') === false) {
            $class = "Models\\{$class}";
        }

        if (isset($relationship['foreignKey'])) {
            $foreignKey = $relationship['foreignKey'];
        } else {
            if ($relationship['type'] === 'belongsTo') {
                $foreignKey = $class;
            } else if (is_string($this->_model)) {
                $foreignKey = $this->_model;
            } else {
                $foreignKey = get_class($this->_model);
            }
            $foreignKey = array_pop(explode('\\', $foreignKey));
            $foreignKey = Helper::underscore($foreignKey) . '_id';
        }

        $model = $this->_model;
        $conditions = array('sql' => '', 'values' => array());
        if (isset($relationship['conditions'])) {
            $conditions = $model::parseConditions($relationship['conditions']);
        }

        if ($relationship['type'] === 'belongsTo') {
            $extraCondition = 'id = ?';
        } else {
            $extraCondition = "{$foreignKey} = ?";
        }
        if (empty($conditions['sql'])) {
            $conditions['sql'] = $extraCondition;
        } else {
            $conditions['sql'] = "({$conditions['sql']}) and {$extraCondition}";
        }

        $metadata = array(
            'class' => $class,
            'type' => $relationship['type'],
            'foreignKey' => $foreignKey,
            'conditions' => $conditions,
            'withDeleted' => isset($relationship['withDeleted']) && $relationship['withDeleted'] === true
        );

        if (isset($relationship['select'])) {
            $metadata['select'] = $relationship['select'];
        }

        if ($relationship['type'] !== 'belongsTo') {
            $metadata['dependent'] = 'update';
            if (isset($relationship['dependent'])) {
                if ($relationship['dependent'] === 'delete' && $class::isSoftDeleteable()) {
                    $metadata['dependent'] = 'delete';
                } else {
                    $metadata['dependent'] = 'destroy';
                }
            }
        }

        return $metadata;
    }

    private function _parseConditions($metadata)
    {
        $result = $metadata['conditions']['values'];
        if ($metadata['type'] === 'belongsTo') {
            array_push($result, $metadata['foreignKeyData'][$metadata['foreignKey']]);
        } else {
            array_push($result, $metadata['foreignKeyData']['id']);
        }

        array_unshift($result, $metadata['conditions']['sql']);
        return $result;
    }

    private function _parseRecord($metadata)
    {
        $options = array(
            'conditions' => $this->_parseConditions($metadata),
            'withDeleted' => $metadata['withDeleted'],
            'toArray' => $metadata['toArray'],
            'withRelationships' => $metadata['withRelationships']
        );

        if ($metadata['type'] === 'hasMany') {
            return $metadata['class']::find($options);
        }

        return $metadata['class']::first($options);
    }
}