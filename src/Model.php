<?php
namespace Clown;

use Clown\Iterators\Model as ModelIterator;

class Model
{
    const NEW_RECORD_KEY = '11473a5e16c71d42be29874c4d557864';

    private $_newRecord = true;
    private $_destroyedRecord = false;

    private $_attributes = array();
    private $_aliasAttributes = array();
    private $_changedAttributes = array();
    private $_lazyParseAttributes = true;

    private $_callback = null;
    private $_validation = null;
    private $_relationship = null;

    public static $table = null;
    public static $primaryKey = 'id';

    public static $has_one = array();
    public static $has_many = array();
    public static $belongs_to = array();

    public static function checkWhetherMissingPrimaryKey()
    {
        if (static::$primaryKey === null) {
            throw new MissingPrimaryKeyException(get_called_class());
        }
    }

    public static function validateAttributes($attributes)
    {
        $columns = static::columns();
        foreach ($columns as $key => $column) {
            if (array_key_exists($key, $attributes)) {
                $attributes[$key] = Column::instance()->parseValue(
                    $attributes[$key], $column['type']
                );
            }
        }

        return $attributes;
    }

    public static function table()
    {
        static $table = null;
        if ($table !== null) {
            return $table;
        }

        if (!empty(static::$table)) {
            return static::$table;
        }

        $parts = explode('\\', get_called_class());
        $table = Helper::pluralize(Helper::underscore(array_pop($parts)));
        return $table;
    }

    public static function columns()
    {
        static $columns = null;
        if ($columns === null) {
            $columns = Column::instance()->get(static::table());
        }
        return $columns;
    }

    public static function find($options = array())
    {
        if(!array_key_exists('order', $options) && static::$primaryKey !== null) {
            $options['order'] = static::$primaryKey . ' asc';
        }

        $params = SqlBuilder::parseQuerySql(static::table(), $options);
        return new ModelIterator(
            Connection::instance()->fetch($params['sql'], $params['values']),
            get_called_class()
        );
    }

    public static function first($options = array())
    {
        $options['limit'] = 1;
        $options['offset'] = 0;
        $records = static::find($options);
        return (empty($records) ? null : $records[0]);
    }

    public static function last($options = array())
    {
        if (!empty($options['order'])) {
            $matches = array();
            preg_match_all('/^\s*(\S+)\s+(asc|desc)$/i', $options['order'], $matches);
            if (!empty($matches[1][0]) && !empty($matches[2][0])) {
                $colunm = $matches[1][0];
                $orderType = strtolower($matches[2][0]);
                if ($orderType === 'desc') {
                    $options['order'] = $colunm . ' asc';
                } else {
                    $options['order'] = $colunm . ' desc';
                }
            } else {
                unset($options['order']);
            }
        } else if (static::$primaryKey !== null) {
            $options['order'] = static::$primaryKey . ' desc';
        }
        if (!empty($options['order'])) {
            $options['limit'] = 1;
            $options['offset'] = 0;
        }

        $records = static::find($options);
        return (empty($records) ? null : $records[count($records) - 1]);
    }

    public static function count($options = array())
    {
        $options['select'] = array('count(id)');
        return static::first($options)->toArray()['count(id)'];
    }

    public static function exists($options = array())
    {
        return (static::count($options) > 0);
    }

    public static function create($attributes)
    {
        $attributes = static::validateAttributes($attributes);
        if (array_key_exists('created_at', static::columns()) &&
            empty($attributes['created_at'])
        ) {
            $attributes['created_at'] = date('Y-m-d H:i:s');
        }
        if (array_key_exists('updated_at', static::columns()) &&
            empty($attributes['updated_at'])
        ) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $params = SqlBuilder::parseInsertSql(static::table(), $attributes);
        $lastInsertId = Connection::instance()->insert($params['sql'], $params['values']);
        if ($lastInsertId > 0 && static::$primaryKey !== null) {
            $attributes[static::$primaryKey] = $lastInsertId;
        }
        return new static($attributes, false);
    }

    public static function updateAll($attributes, $conditions = array())
    {
        if (empty($attributes)) {
            return true;
        }

        $attributes = static::validateAttributes($attributes);
        if (array_key_exists('updated_at', static::columns()) &&
            empty($attributes['updated_at'])
        ) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $params = SqlBuilder::parseUpdateSql(static::table(), $attributes, $conditions);
        return Connection::instance()->update($params['sql'], $params['values']);
    }

    public static function destroyAll($conditions = array())
    {
        $params = SqlBuilder::parseDestroySql(static::table(), $conditions);
        return Connection::instance()->execute($params['sql'], $params['values']);
    }

    public static function __callStatic($method, $arguments)
    {
        if (!preg_match('/^find_by_|find_or_create_by_/', $method)) {
            throw new UndefinedMethodException($method, get_called_class());
        }

        if (count($arguments) <= 0) {
            throw new MissingArgumentException($method, get_called_class());
        }

        $_method = preg_replace('/^find_by_|find_or_create_by_/', '', $method);
        if (preg_match('/_and_/', $_method)) {
            $parts = explode('_and_', $_method);
            if (count($arguments) < 2) {
                throw new MissingArgumentException($method, get_called_class(), 2);
            }
            $attributes = array($parts[0] => $arguments[0], $parts[1] => $arguments[1]);
            $conditions = array("{$parts[0]} = ? AND {$parts[1]} = ?", $arguments[0], $arguments[1]);
        } else if (preg_match('/_or_/', $_method)) {
            $parts = explode('_or_', $_method);
            if (count($arguments) < 2) {
                throw new MissingArgumentException($method, get_called_class(), 2);
            }
            $attributes = array($parts[0] => $arguments[0], $parts[1] => $arguments[1]);
            $conditions = array("{$parts[0]} = ? OR {$parts[1]} = ?", $arguments[0], $arguments[1]);
        } else {
            $attributes = array($_method => $arguments[0]);
            $conditions = array($_method => $arguments[0]);
        }

        if (strpos($method, 'find_or_create_by_') !== 0) {
            return static::find(array('conditions' => $conditions));
        }

        $record = static::first(array('conditions' => $conditions));
        if (!empty($record)) {
            return $record;
        }

        return new static($attributes, true);
    }

    public function checkWhetherRecordOperateable()
    {
        if ($this->_destroyedRecord) {
            throw new OperateDestroyedRecordException();
        }
    }

    public function primary()
    {
        static::checkWhetherMissingPrimaryKey();
        $primaryKey = static::$primaryKey;
        return $this->$primaryKey;
    }

    public function primaryWas()
    {
        static::checkWhetherMissingPrimaryKey();
        $primaryWasKey = static::$primaryKey . '_was';
        return $this->$primaryWasKey;
    }

    public function isNewRecord()
    {
        return $this->_newRecord;
    }

    public function isValid()
    {
        return $this->validation->validate();
    }

    public function errors($attribute = null)
    {
        return $this->validation->errors($attribute);
    }

    public function addError($attribute, $message)
    {
        $this->validation->addError($attribute, $message);
        return $this;
    }

    public function toArray()
    {
        $this->lazyParseAttributes();

        $result = array();
        foreach ($this->_attributes as $key => $value) {
            $result[$key] = $value;
        }
        foreach ($this->_aliasAttributes as $key => $value) {
            $result[$key] = $value;
        }
        return $result;
    }

    public function save($validate = true)
    {
        if ($this->_newRecord) {
            return $this->insert($validate);
        }

        return $this->update($validate);
    }

    public function insert($validate = true)
    {
        $this->checkWhetherRecordOperateable();
        $this->lazyParseAttributes();

        if ($validate === true && $this->isValid() === false) {
            return false;
        }

        if ($this->callback->call('before_create') === false) {
            return false;
        }

        $status = $this->relationship->create(function() {
            $result = static::create($this->_attributes);
            if ($result === false) {
                return false;
            }
            $this->_newRecord = false;
            $this->resetAttributes($result->toArray());
        });

        if ($status === false) {
            return false;
        }

        $this->callback->call('after_create');
        return true;
    }

    public function update($validate = true)
    {
        $this->checkWhetherRecordOperateable();
        static::checkWhetherMissingPrimaryKey();

        $this->lazyParseAttributes();

        if ($validate === true && $this->isValid() === false) {
            return false;
        }

        if ($this->callback->call('before_update') === false) {
            return false;
        }

        $status = $this->relationship->update(function() {
            $result = static::updateAll(
                $this->_changedAttributes,
                array(static::$primaryKey => $this->primaryWas())
            );
            if ($result === false) {
                return false;
            }

            foreach ($this->_changedAttributes as $key => $value) {
                $this->_attributes[$key] = $value;
            }
            $this->_changedAttributes = array();
        });

        if ($status === false) {
            return false;
        }

        $this->callback->call('after_update');
        return true;
    }

    public function updateAttributes($attributes, $validate = true)
    {
        $this->_changedAttributes = $attributes;
        return $this->update($validate);
    }

    public function destroy()
    {
        $this->checkWhetherRecordOperateable();
        static::checkWhetherMissingPrimaryKey();
        $this->lazyParseAttributes();

        if ($this->callback->call('before_destroy') === false) {
            return false;
        }

        $status = $this->relationship->destroy(function() {
            $result = static::destroyAll(
                array(static::$primaryKey => $this->primaryWas())
            );

            if ($result === false) {
                return false;
            }

            $this->_destroyedRecord = true;
            $this->_attributes = array();
            $this->_aliasAttributes = array();
            $this->_changedAttributes = array();
        });

        if ($status === false) {
            return false;
        }

        $this->callback->call('after_destroy');
        return true;
    }

    public function parseAttributes($attributes)
    {
        $result = array();
        $columns = static::columns();

        foreach ($columns as $key => $column) {
            if (array_key_exists($key, $attributes)) {
                $result[$key] = Column::instance()->parseValue(
                    $attributes[$key], $column['type']
                );
            } else if (array_key_exists('default', $column)) {
                $result[$key] = $column['default'];
            } else {
                $result[$key] = null;
            }
        }

        foreach ($attributes as $key => $value) {
            if (array_key_exists($key, $columns) === false) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public function resetAttributes($attributes)
    {
        $columns = static::columns();
        $this->_attributes = array();
        $this->_aliasAttributes = array();
        foreach ($attributes as $key => $value) {
            if ($key !== static::NEW_RECORD_KEY) {
                if (array_key_exists($key, $columns)) {
                    $this->_attributes[$key] = $value;
                } else {
                    $this->_aliasAttributes[$key] = $value;
                }
            }
        }
    }

    public function lazyParseAttributes()
    {
        if ($this->_lazyParseAttributes) {
            $this->resetAttributes(
                $this->parseAttributes($this->_attributes)
            );
            $this->_lazyParseAttributes = false;
        }
    }

    public function getAttribute($attribute)
    {
        $this->lazyParseAttributes();

        if (preg_match('/_was$/', $attribute)) {
            $attribute = preg_replace('/_was$/', '', $attribute);
        } else {
            if (array_key_exists($attribute, $this->_changedAttributes)) {
                return $this->_changedAttributes[$attribute];
            }
        }

        $attributeGruops = array($this->_attributes, $this->_aliasAttributes);
        foreach ($attributeGruops as $attributes) {
            if (array_key_exists($attribute, $attributes)) {
                return $attributes[$attribute];
            }
        }

        $relationship = $this->relationship->get($attribute);
        if ($relationship !== false) {
            return $relationship;
        }

        throw new UndefinedPropertyException($attribute, get_called_class());
    }

    public function setAttribute($attribute, $value)
    {
        $this->lazyParseAttributes();

        if (array_key_exists($attribute, $this->_attributes)) {
            $value = Column::instance()->parseValue(
                $value, static::columns()[$attribute]['type']
            );
            if ($this->_newRecord) {
                $this->_attributes[$attribute] = $value;
            } else {
                if ($this->_attributes[$attribute] === $value) {
                    unset($this->_changedAttributes[$attribute]);
                } else {
                    $this->_changedAttributes[$attribute] = $value;
                }
            }

            return $this;
        }

        if ($this->relationship->set($attribute, $value) !== false) {
            return $this;
        }

        if (array_key_exists($attribute, $this->_aliasAttributes)) {
            throw new PropertyReadOnlyException($attribute, get_called_class());
        }

        throw new UndefinedPropertyException($attribute, get_called_class());
    }

    public function __get($attribute)
    {
        if ($attribute === 'callback') {
            if ($this->_callback === null) {
                $this->_callback = new Callback($this);
            }

            return $this->_callback;
        }

        if ($attribute === 'validation') {
            if ($this->_validation === null) {
                $this->_validation = new Validation($this);
            }

            return $this->_validation;
        }

        if ($attribute === 'relationship') {
            if ($this->_relationship === null) {
                $this->_relationship = new Relationship($this);
            }

            return $this->_relationship;
        }

        $this->checkWhetherRecordOperateable();

        $method = 'get_' . $attribute;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->getAttribute($attribute);
    }

    public function __set($attribute, $value)
    {
        $this->checkWhetherRecordOperateable();

        if (in_array($attribute, array('callback', 'validation', 'relationship'))) {
            throw new PropertyReadOnlyException($attribute, get_called_class());
        }

        $method = 'set_' . $attribute;
        if (method_exists($this, $method)) {
            $this->$method($value);
            return $this;
        }

        return $this->setAttribute($attribute, $value);
    }

    public function __isset($attribute)
    {
        if (in_array($attribute, array('callback', 'validation', 'relationship'))) {
            return true;
        }

        $this->lazyParseAttributes();

        if (preg_match('/_was$/', $attribute)) {
            $attribute = preg_replace('/_was$/', '', $attribute);
        } else {
            if (array_key_exists($attribute, $this->_changedAttributes)) {
                return true;
            }
        }

        $attributeGruops = array($this->_attributes, $this->_aliasAttributes);
        foreach ($attributeGruops as $attributes) {
            if (array_key_exists($attribute, $attributes)) {
                return true;
            }
        }

        return $this->relationship->exists($attribute);
    }

    public function __call($method, $arguments)
    {
        $this->checkWhetherRecordOperateable();

        if (strpos($method, 'init_') === 0) {
            $relationship = ltrim($method, 'init_');
            $relationshipMetadata = $this->relationship->getMetadata($relationship);
            if ($relationshipMetadata === null) {
                $relationship = Helper::pluralize($relationship);
                $relationshipMetadata = $this->relationship->getMetadata($relationship);
                if ($relationship === null) {
                    throw new UndefinedMethodException($method, get_called_class());
                }
            }
            $this->$relationship = new $relationshipMetadata['class']($arguments[0]);
            return true;
        }

        if (preg_match('/_was_changed$/', $method)) {
            $attribute = preg_replace('/_was_changed$/', '', $method);
            if (array_key_exists($attribute, $this->_attributes)) {
                return array_key_exists($attribute, $this->_changedAttributes);
            }
        }
        throw new UndefinedMethodException($method, get_called_class());
    }

    public function __construct($attributes = array(), $newRecord = true)
    {
        if (isset($attributes[static::NEW_RECORD_KEY])) {
            $this->_newRecord = $attributes[static::NEW_RECORD_KEY];
        } else {
            $this->_newRecord = $newRecord;
        }

        $this->_attributes = $attributes;
        $this->_lazyParseAttributes = true;
    }
}
