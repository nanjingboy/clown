<?php
namespace Clown;

use Clown\Iterators\Model as ModelIterator;

class Model
{
    const NEW_RECORD_KEY = '11473a5e16c71d42be29874c4d557864';

    private $_callback = null;
    private $_newRecord = true;
    private $_destroyedRecord = false;

    private $_relationship = null;
    private $_validation = null;

    private $_attributes = array();
    private $_aliasAttributes = array();
    private $_changedAttributes = array();

    public static $disabled = 'disabled';
    public static $table = null;
    public static $defaultOrder = 'id asc';

    public static $hasOne = array();
    public static $hasMany = array();
    public static $belongsTo = array();

    public static function table()
    {
        if (static::$table === null) {
            return Helper::pluralize(
                Helper::underscore(array_pop(explode('\\', get_called_class())))
            );
        }

        return static::$table;
    }

    public static function columns()
    {
        return Column::instance()->get(static::table());
    }

    public static function isSoftDeleteable()
    {
        return array_key_exists(static::$disabled, static::columns());
    }

    public static function parseAttributes($attributes)
    {
        $result = array();
        $columns = static::columns();
        foreach ($columns as $key => $column) {
            if (array_key_exists(Helper::underscore($key), $attributes) ||
                array_key_exists(Helper::camelize($key, false), $attributes)
            ) {
                $result[$key] = Column::instance()->parseValueWithType(
                    $attributes[$key], $column['type']
                );
            } else {
                $result[$key] = $column['default'];
            }
        }

        foreach ($attributes as $key => $value) {
            if (!array_key_exists($key, $columns)) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    public static function parseConditions($conditions)
    {
        if (is_string($conditions)) {
            return array(
                'sql' => $conditions,
                'values' => array()
            );
        } else if (is_array($conditions)) {
            if ($conditions !== array_values($conditions)) {
                $tmp = array(array());
                foreach ($conditions as $key => $value) {
                    if (is_array($value)) {
                        array_push($tmp[0], "{$key} in (?)");
                    } else {
                        array_push($tmp[0], "{$key} = ?");
                    }
                    array_push($tmp, $value);
                }
                $tmp[0] = implode(' and ', $tmp[0]);
                $conditions = $tmp;
            }
            $sql = array_shift($conditions);
            $values = array();
            foreach ($conditions as $value) {
                if (is_array($value)) {
                    array_push($values, implode(',', $value));
                } else {
                    array_push($values, $value);
                }
            }
            return array('sql' => $sql, 'values' => $values);
        }
    }

    public static function find()
    {
        $options = array();
        $arguments = func_get_args();
        if (!empty($arguments)) {
            if (count($arguments) === 2) {
                $options = $arguments[1];
                $options['conditions'] = array('id' => $arguments[0]);
                $options['onlyOneRecord'] = (is_array($arguments[0]) ? false : true);
            } else if (is_array($arguments[0])) {
                if ($arguments[0] === array_values($arguments[0])) {
                    $options = array('conditions' => array('id' => $arguments[0]));
                } else {
                    $options = $arguments[0];
                }
            } else if ($arguments[0] !== null) {
                $options = array('conditions' => array('id' => $arguments[0]));
                $options['onlyOneRecord'] = (is_array($arguments[0]) ? false : true);
            }
        }

        if (empty($options['select'])) {
            $select = '*';
        } else {
            $select = implode(',', $options['select']);
        }

        $sql = 'SELECT ' . $select  . ' FROM ' . static::table();

        if (static::isSoftDeleteable()) {
            if (!isset($options['withDeleted']) || $options['withDeleted'] === false) {
                $sql .= ' WHERE ' . static::$disabled . ' = 0';
            } else {
                $sql .= ' WHERE 1';
            }
        } else {
            $sql .= ' WHERE 1';
        }

        $conditions = array('sql' => null, 'values' => array());
        if (!empty($options['conditions'])) {
            $conditions = static::parseConditions($options['conditions']);
            $sql .= " AND ({$conditions['sql']})";
        }

        if (isset($options['group'])) {
            $sql .= " GROUP BY {$options['group']}";
        }

        if (isset($options['having'])) {
            $having = static::parseConditions($options['having']);
            if (!empty($having['values'])) {
                $conditions['values'] = array_merge(
                    $conditions['values'], $having['values']
                );
            }

            $sql .= " HAVING {$having['sql']}";
        }

        if (isset($options['order'])) {
            $sql .= " ORDER BY {$options['order']}";
        } else {
            $sql .= ' ORDER BY ' . static::$defaultOrder;
        }

        if (isset($options['limit'])) {
            $options['offset'] = (isset($options['offset']) ? $options['offset'] : 0);
            $sql .= " LIMIT {$options['offset']},{$options['limit']}";
        }

        $records = new ModelIterator(
            Db::instance()->fetch($sql, $conditions['values']),
            get_called_class(),
            isset($options['toArray']) && $options['toArray'] === true,
            isset($options['withRelationships']) && $options['withRelationships'] === true
        );
        if (isset($options['onlyOneRecord']) && $options['onlyOneRecord'] === true) {
            return $records[0];
        }

        return $records;
    }

    public static function count($options = null)
    {
        $options = ($options === null ? array() : $options);
        $options['select'] = array('count(id)');
        $options['toArray'] = true;
        return static::first($options)['count(id)'];
    }

    public static function create($attributes)
    {
        $attributes = static::parseAttributes($attributes);
        if (!empty($attributes['id'])) {
            unset($attributes['id']);
        }

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

        $sql = 'INSERT INTO ' . static::table() . ' SET ' .
                '`' . implode('`=?,`', array_keys($attributes)) . '`=?';
        $attributes['id'] = Db::instance()->insert($sql, array_values($attributes));
        $model = get_called_class();
        return new $model($attributes, false, false);
    }

    public static function updateAll($attributes, $conditions = null)
    {
        if (empty($attributes)) {
            return true;
        }

        if (!empty($attributes['id'])) {
            unset($attributes['id']);
        }

        $validAttributes = static::parseAttributes($attributes);
        foreach ($attributes as $key => $value) {
            $attributes[$key] = $validAttributes[$key];
        }

        if (array_key_exists('updated_at', static::columns()) &&
            empty($attributes['updated_at'])
        ) {
            $attributes['updated_at'] = date('Y-m-d H:i:s');
        }

        $sql = 'UPDATE ' . static::table() . ' SET ' .
                '`' . implode('`=?,`', array_keys($attributes)) . '`=?';

        $values = array_values($attributes);
        if (!empty($conditions)) {
            $conditions = static::parseConditions($conditions);
            $sql .= ' WHERE ' . $conditions['sql'];
            $values = array_merge($values, $conditions['values']);
        }

        return Db::instance()->update($sql, $values);
    }

    public static function deleteAll($conditions = null)
    {
        if (static::isSoftDeleteable()) {
            return static::updateAll(array(static::$disabled => true), $conditions);
        }
        return static::destroyAll($conditions);
    }

    public static function restoreAll($conditions = null)
    {
        if (static::isSoftDeleteable()) {
            return static::updateAll(array(static::$disabled => false), $conditions);
        }
        return true;
    }

    public static function destroyAll($conditions = null)
    {
        $sql = 'DELETE FROM ' . static::table();

        $values = array();
        if (!empty($conditions)) {
            $conditions = static::parseConditions($conditions);
            $sql .= ' WHERE ' . $conditions['sql'];
            $values = $conditions['values'];
        }

        return Db::instance()->execute($sql, $values);
    }

    public static function __callStatic($method, $arguments)
    {
        $options = array();
        if (in_array($method, array('first', 'last'))) {
            if (!empty($arguments)) {
                $options = $arguments[0];
            }
            $options['limit'] = 1;
            $options['offset'] = 0;
            $options['onlyOneRecord'] = true;
            if ($method === 'last' && !array_key_exists('order', $options)) {
                $options['order'] = 'id desc';
            }
        } else if (preg_match('/^findBy[A-Z][A-Za-z]+$/', $method)) {
            if (count($arguments) <= 0) {
                throw new MissingArgumentException($method, get_called_class());
            }

            $parts = preg_split(
                '/(And)|(Or)/',
                str_replace('findBy', '', $method),
                -1,
                PREG_SPLIT_DELIM_CAPTURE
            );
            foreach ($parts as $index => $part) {
                $parts[$index] = Helper::underscore($part);
            }

            if (count($parts) === 3) {
                if (count($arguments) < 2) {
                    throw new MissingArgumentException($method, get_called_class(), 2);
                }

                if (!empty($arguments[2])) {
                    $options = $arguments[2];
                }
                $options['conditions'] = array(
                    "{$parts[0]} = ? {$parts[1]} {$parts[2]} = ?",
                    array($arguments[0], $arguments[1])
                );
            } else {
                if (!empty($arguments[1])) {
                    $options = $arguments[1];
                }
                $options['conditions'] = array($parts[0] => $arguments[0]);
            }
        } else {
            throw new UndefinedMethodException($method, get_called_class());
        }
        return static::find($options);
    }

    public function isNewRecord()
    {
        return $this->_newRecord;
    }

    public function isValid()
    {
        return $this->_validation->validate();
    }

    public function errors($attribute = null)
    {
        if ($attribute === null) {
            return $this->_validation->errors;
        }

        if (array_key_exists($attribute, $this->_validation->errors)) {
            return $this->_validation->errors[$attribute];
        }

        return null;
    }

    public function addError($attribute, $message)
    {
        if (array_key_exists($attribute, $this->_validation->errors) === false) {
            $this->_validation->errors[$attribute] = array();
        }

        array_push($this->_validation->errors[$attribute], $message);
    }

    public function save($validate = true)
    {
        if ($validate === true && $this->isValid() === false) {
            return false;
        }

        if ($this->_newRecord) {
            return $this->insert();
        }

        return $this->update();
    }

    public function toArray()
    {
        $result = array();
        foreach ($this->_attributes as $key => $value) {
            $result[$key] = $value;
        }

        foreach ($this->_aliasAttributes as $key => $value) {
            $result[$key] = $value;
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
                $underscoreKey = Helper::underscore($key);
                $camelizeKey = Helper::camelize($key, false);
                if (array_key_exists($underscoreKey, $columns)) {
                    $this->_attributes[$underscoreKey] = $value;
                } else if (array_key_exists($camelizeKey, $columns)) {
                    $this->_attributes[$camelizeKey] = $value;
                } else {
                    $this->_aliasAttributes[$key] = $value;
                }
            }
        }
    }

    public function __construct($attributes = array(), $newRecord = true, $parseAttributes = true)
    {
        if (isset($attributes[static::NEW_RECORD_KEY])) {
            $this->_newRecord = $attributes[static::NEW_RECORD_KEY];
        } else {
            $this->_newRecord = $newRecord;
        }

        if ($parseAttributes === true) {
            $attributes = static::parseAttributes($attributes);
        }
        $this->resetAttributes($attributes);

        $this->_callback = new Callback($this);
        $this->_relationship = new Relationship($this);
        $this->_validation = new Validation($this);
    }

    public function __get($attribute)
    {
        if ($this->_destroyedRecord) {
            throw new OperateDestroyedRecordException();
        }

        if ($attribute === 'callback') {
            return $this->_callback;
        } else if ($attribute === 'changedAttributes') {
            return $this->_changedAttributes;
        }

        $method = 'get' . Helper::camelize($attribute);
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        $underscoreAttribute = Helper::underscore($attribute);
        $camelizeAttribute = Helper::camelize($underscoreAttribute, false);
        if (preg_match('/^[a-z0-9]+_was$/', $underscoreAttribute)) {
            $underscoreAttribute = str_replace('_was', '', $underscoreAttribute);
            $camelizeAttribute = Helper::camelize($underscoreAttribute, false);
        } else {
            if (array_key_exists($underscoreAttribute, $this->_changedAttributes)) {
                return $this->_changedAttributes[$underscoreAttribute];
            }

            if (array_key_exists($camelizeAttribute, $this->_changedAttributes)) {
                return $this->_changedAttributes[$camelizeAttribute];
            }
        }

        $attributeGruops = array($this->_attributes, $this->_aliasAttributes);
        foreach ($attributeGruops as $attributes) {
            if (array_key_exists($underscoreAttribute, $attributes)) {
                return $attributes[$underscoreAttribute];
            }

            if (array_key_exists($camelizeAttribute, $attributes)) {
                return $attributes[$camelizeAttribute];
            }
        }

        $relationship = $this->_relationship->get($attribute);
        if ($relationship !== false) {
            return $relationship;
        }

        throw new UndefinedPropertyException($attribute, get_called_class());
    }

    public function __set($attribute, $value)
    {
        if ($this->_destroyedRecord) {
            throw new OperateDestroyedRecordException();
        }

        if (in_array($attribute, array('id', 'callback', 'changedAttributes', static::$disabled))) {
            throw new PropertyReadOnlyException($attribute, get_called_class());
        }

        $method = 'set' . Helper::camelize($attribute);
        if (method_exists($this, $method)) {
            return $this->$method($value);
        }

        $underscoreAttribute = Helper::underscore($attribute);
        $camelizeAttribute = Helper::camelize($underscoreAttribute, false);
        foreach (array($underscoreAttribute, $camelizeAttribute) as $key) {
            if (array_key_exists($key, $this->_attributes)) {
                $value = Column::instance()->parseValueWithType(
                    $value, static::columns()[$key]['type']
                );
                if ($this->_newRecord) {
                    $this->_attributes[$key] = $value;
                } else {
                    if ($this->_attributes[$key] === $value) {
                        unset($this->_changedAttributes[$key]);
                    } else {
                        $this->_changedAttributes[$key] = $value;
                    }
                }
                return;
            }
        }

        if (array_key_exists($underscoreAttribute, $this->_aliasAttributes) ||
            array_key_exists($camelizeAttribute, $this->_aliasAttributes)) {
            throw new PropertyReadOnlyException($attribute, get_called_class());
        } else if ($this->_relationship->set($attribute, $value) === false) {
            throw new UndefinedPropertyException($attribute, get_called_class());
        }
    }

    public function __call($method, $arguments)
    {
        if ($this->_destroyedRecord) {
            throw new OperateDestroyedRecordException();
        }

        if (preg_match('/^[A-Za-z0-9]+WasChanged$/', $method)) {
            $underscoreAttribute = Helper::underscore(
                str_replace('WasChanged', '', $method)
            );
            $camelizeAttribute = Helper::camelize($underscoreAttribute, false);
            if (array_key_exists($underscoreAttribute, $this->_attributes)) {
                return array_key_exists($underscoreAttribute, $this->_changedAttributes);
            }
            if (array_key_exists($camelizeAttribute, $this->_attributes)) {
                return array_key_exists($camelizeAttribute, $this->_changedAttributes);
            }
        }

        if (!in_array($method, array('insert', 'update', 'delete', 'restore', 'destroy'))) {
            throw new UndefinedMethodException($method, get_called_class());
        }

        if ($method === 'restore' && static::isSoftDeleteable() === false) {
            return true;
        }

        if ($method === 'delete' && static::isSoftDeleteable() === false) {
            $method = 'destroy';
        } else if ($method === 'insert' && $this->_newRecord === false) {
            $method = 'update';
        }

        if ($this->_callback->call('before' . ucfirst($method)) === false) {
            return false;
        }

        if ($method === 'insert') {
            $result = $this->_relationship->create(function() {
                $result = static::create($this->_attributes);
                if ($result === false) {
                    return false;
                }
                $this->_newRecord = false;
                $this->resetAttributes($result->toArray());
            });
        } else if (in_array($method, array('update', 'delete', 'restore'))) {
            if ($method === 'delete') {
                $this->_changedAttributes[static::$disabled] = true;
            } else if ($method === 'restore') {
                $this->_changedAttributes[static::$disabled] = false;
            }

            if ($method === 'update') {
                $result = $this->_relationship->update(function() {
                    return static::updateAll(
                        $this->_changedAttributes, array('id' => $this->idWas)
                    );
                });
            } else {
                $result = static::updateAll(
                    $this->_changedAttributes, array('id' => $this->idWas)
                );
            }

            if ($result !== false) {
                foreach ($this->_changedAttributes as $key => $value) {
                    $this->_attributes[$key] = $value;
                }
                $this->_changedAttributes = array();
            }
        } else {
            $result = $this->_relationship->destroy(function() {
                return static::destroyAll(array('id' => $this->idWas));
            });

            if ($result !== false) {
                $this->_destroyedRecord = true;
                $this->_attributes = array();
                $this->_aliasAttributes = array();
                $this->_changedAttributes = array();
            }
        }

        if ($result !== false) {
            $this->_callback->call('after' . ucfirst($method));
        }

        return $result;
    }
}
