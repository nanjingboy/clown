<?php
namespace Clown\Relationship;

use Clown\Helper;
use Clown\SqlBuilder;
use Clown\Iterators\Model as ModelIterator;

abstract class Base
{
    protected $_model = null;
    protected $_metadata = array();
    protected $_records = array();

    abstract protected function _parseForeignClass($metadata);
    abstract protected function _parseDefaultCondition($metadata);
    abstract protected function _parseRecord($metadata);
    abstract protected function _save();

    abstract public function set($name, $model);
    abstract public function destroy();

    protected function _parseForeignKey($metadata)
    {
        if (!empty($metadata['foreign_key'])) {
            return $metadata['foreign_key'];
        }

        $class = $this->_parseForeignClass($metadata);
        $namespaces = explode('\\', $class);
        return Helper::underscore(
            array_pop($namespaces)
        ) . '_' . $class::$primaryKey;
    }

    protected function _parseConditions($metadata)
    {
        $conditions = array('sql' => '', 'values' => array());
        if (isset($metadata['conditions'])) {
            $conditions = SqlBuilder::parseConditions($metadata['conditions']);
        }

        $defaultCondition = $this->_parseDefaultCondition($metadata);
        if (empty($conditions['sql'])) {
            $conditions['sql'] = $defaultCondition['sql'];
        } else {
            $conditions['sql'] = "({$conditions['sql']}) AND ({$defaultCondition['sql']})";
        }
        array_push($conditions['values'], $defaultCondition['value']);

        return array_merge(array($conditions['sql']), $conditions['values']);
    }

    protected function _parseMetadata($metadata)
    {
        $result = array(
            'class' => $metadata['class'],
            'foreign_key' => $this->_parseForeignKey($metadata),
            'conditions' => $this->_parseConditions($metadata)
        );

        if (!empty($metadata['select'])) {
            $result['select'] = $metadata['select'];
        }

        if (!empty($metadata['dependent'])) {
            $result['dependent'] = $metadata['dependent'];
        }

        return $result;
    }

    public function __construct($model)
    {
        $this->_model = $model;
        $namespaces = explode('\\', get_called_class());
        $type = Helper::underscore(array_pop($namespaces));
        $properties = get_class_vars(get_class($model));
        if (!empty($properties[$type])) {
            foreach ($properties[$type] as $metadata) {
                $name = array_shift($metadata);
                if (empty($metadata['class'])) {
                    $metadata['class'] = ucfirst(Helper::singularize($name));
                }
                $metadata['class'] = '\\' . ltrim($metadata['class'], '\\');
                $this->_metadata[$name] = $metadata;
            }
        }
    }

    public function exists($name)
    {
        return array_key_exists($name, $this->_metadata);
    }

    public function get($name)
    {
        if ($this->exists($name) === false) {
            return false;
        }

        if (empty($this->_records[$name]) ||
            (($this->_records[$name] instanceof ModelIterator) &&
                count($this->_records[$name]) < 1
            )) {
            $this->_records[$name] = $this->_parseRecord(
                $this->_parseMetadata($this->_metadata[$name])
            );
        }

        return $this->_records[$name];
    }

    public function getMetadata($name = null)
    {
        if ($name === null) {
            return $this->_metadata;
        }

        if ($this->exists($name) === false) {
            return null;
        }

        return $this->_metadata[$name];
    }

    public function create()
    {
        $this->_save();
    }

    public function update()
    {
        $this->_save();
    }
}