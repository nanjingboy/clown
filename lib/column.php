<?php
namespace Clown;

use Exception;

class Column extends Singleton
{
    public static $TYPES = array(
        'string' => array('name' => 'varchar', 'length' => 255),
        'text' => array('name' => 'text'),
        'integer' => array('name' => 'int', 'length' => 11),
        'float' => array('name' => 'float'),
        'datetime' => array('name' => 'datetime'),
        'timestamp' => array('name' => 'timestamp'),
        'time' => array('name' => 'time'),
        'date' => array('name' => 'date'),
        'binary' => array('name' => 'blob'),
        'boolean' => array('name' => 'tinyint', 'length' => 1)
    );

    public function get($table, $column = null)
    {
        $columns = Cache::get($this->cacheKey($table));
        if (empty($columns)) {
            $columns = Db::instance()->fetch("desc {$table}");
            $tmp = array();
            foreach ($columns as $item) {
                $tmp[$item['field']] = $this->parseOptionsFromDatabase($item);
            }
            $columns = $tmp;
            Cache::set($this->cacheKey($table), $columns);
        }

        if ($column === null) {
            return $columns;
        }

        return (isset($columns[$column]) ? $columns[$column] : null);
    }

    public function add($table, $column, $type, $options = array())
    {
        $options['type'] = $type;
        $options = $this->parseOptionsToDatabase($options);
        $sql = "ALTER TABLE `{$table}` ADD `{$column}` {$options['type']}";
        if (isset($options['default'])) {
            $sql = "{$sql} DEFAULT '{$options['default']}'";
        }
        return $this->save($table, $sql);
    }

    public function rename($table, $oldColumn, $newColumn)
    {
        $options = $this->get($table, $oldColumn);
        $options['newColumn'] = $newColumn;
        return $this->update($table, $oldColumn, $options['type'], $options);
    }

    public function update($table, $column, $type, $options = array())
    {
        $newColumn = (isset($options['newColumn']) ? $options['newColumn'] : $column);
        $options['type'] = $type;
        $options = $this->parseOptionsToDatabase($options);
        $sql = "ALTER TABLE `{$table}` CHANGE `{$column}` `{$newColumn}` {$options['type']}";
        if (isset($options['default'])) {
            $sql = "{$sql} DEFAULT '{$options['default']}'";
        }
        return $this->save($table, $sql);
    }

    public function remove($table, $column)
    {
        return $this->save($table, "ALTER TABLE `{$table}` DROP `{$column}`");
    }

    public function parseOptionsToDatabase($column)
    {
        if (!array_key_exists($column['type'], static::$TYPES)) {
            throw new UndefinedColumnTypeException(
                "Undefined column type: {$column['type']}"
            );
        }

        if ($column['type'] == 'boolean') {
            $type = 'tinyint(1)';
        } else {
            $type = static::$TYPES[$column['type']]['name'];
            if (in_array($column['type'], array('string', 'integer'))) {
                if (isset($column['length']) && preg_match('/^-?\d+$/', $column['length'])) {
                    $length = $column['length'];
                } else {
                    $length = static::$TYPES[$column['type']]['length'];
                }
                $type = "{$type}({$length})";
            }
        }

        $default = null;
        if(isset($column['default'])) {
            $default = $this->parseValueWithType($column['default'], $column['type']);
        }

        $result = array('type' => $type);
        if ($default !== null) {
            if ($default === true) {
                $result['default'] = 1;
            } else if ($default === false) {
                $result['default'] = 0;
            } else {
                $result['default'] = $default;
            }
        }

        return $result;
    }

    public function parseValueWithType($value, $type)
    {
        if ($value === null) {
            return null;
        }

        $result = null;
        switch ($type) {
            case 'boolean':
                if ($value === true || preg_match('/^1$/', $value)) {
                    $result = true;
                } else if ($value === false || preg_match('/^0$/', $value)) {
                    $result = false;
                }
                break;
            case 'integer':
                if (preg_match('/^-?\d+$/', $value)) {
                    $result = intval($value);
                }
                break;
            case 'float':
                if (preg_match('/^-?\d+(\.\d+)?$/', $value)) {
                    $result = floatval($value);
                }
                break;
            case 'datetime':
            case 'timestamp':
                $datetime = date_create($value);
                if ($datetime && date_format($datetime, 'Y-m-d H:i:s') === $value) {
                    $result = $value;
                }
                break;
            case 'date':
                $date = date_create($value);
                if ($date && date_format($date, 'Y-m-d') === $value) {
                    $result = $value;
                }
                break;
            case 'time':
                if (preg_match('/^[0-2][0-3]:[0-5][0-9]:[0-5][0-9]$/', $value)) {
                    $result = $value;
                }
                break;
            case 'string':
                $result = $value;
                break;
            default:
                break;
        }
        return $result;
    }

    public function parseOptionsFromDatabase($column)
    {
        $matches = array();
        preg_match_all(
            '/^([a-zA-Z]+)(\((\d+)\))?$/',
            $column['type'],
            $matches
        );
        $type = strtolower($matches[1][0]);
        foreach (static::$TYPES as $key => $columnType) {
            if ($columnType['name'] == $type) {
                $type = $key;
                break;
            }
        }

        return array(
            'id' => $column['field'],
            'default' => static::parseValueWithType($column['default'], $type),
            'type' => $type,
            'length' => intval($matches[3][0])
        );
    }

    public function save($table, $sql)
    {
        try {
            if (Db::instance()->execute($sql) === true) {
                Cache::delete($this->cacheKey($table));
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new ClownException($e);
        }
    }

    public function cacheKey($table)
    {
        return "Columns:{$table}";
    }
}