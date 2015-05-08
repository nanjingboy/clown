<?php
namespace Clown;

class Column extends Singleton
{
    private static $_abilitySetLengthTypes = array(
        'string',
        'varbinary',
        'char',
        'bigint',
        'integer',
        'mediumint',
        'smallint',
        'tinyint'
    );

    private static $_abilitySetUnsignedTypes = array(
        'bigint',
        'integer',
        'mediumint',
        'smallint',
        'tinyint',
        'float',
        'double'
    );

    private static $_abilitySetAutoIncrementTypes = array(
        'bigint',
        'integer',
        'mediumint',
        'smallint',
        'tinyint',
        'float',
        'double'
    );

    private static $_disableSetDefaultValueTypes = array(
        'text',
        'tinytext',
        'mediumtext',
        'longtext',
        'binary'
    );

    public static $TYPES = array(
        'string' => array('name' => 'varchar', 'length' => 255),
        'varbinary' => array('name' => 'varbinary', 'length' => 255),
        'char' => array('name' => 'char', 'length' => 1),
        'bigint' => array('name' => 'bigint', 'length' => 20),
        'integer' => array('name' => 'int', 'length' => 11),
        'mediumint' => array('name' => 'mediumint', 'length' => 9),
        'smallint' => array('name' => 'smallint', 'length' => 6),
        'tinyint' => array('name' => 'tinyint', 'length' => 4),
        'boolean' => array('name' => 'tinyint', 'length' => 1),
        'float' => array('name' => 'float'),
        'double' => array('name' => 'double'),
        'datetime' => array('name' => 'datetime'),
        'timestamp' => array('name' => 'timestamp'),
        'time' => array('name' => 'time'),
        'date' => array('name' => 'date'),
        'text' => array('name' => 'text'),
        'tinytext' => array('name' => 'tinytext'),
        'mediumtext' => array('name' => 'mediumtext'),
        'longtext' => array('name' => 'longtext'),
        'binary' => array('name' => 'blob'),
        'enum' => array('name' => 'enum')
    );

    public function parseValue($value, $type)
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
            case 'bigint':
            case 'integer':
            case 'mediumint':
            case 'smallint':
            case 'tinyint':
                if (preg_match('/^-?\d+$/', $value)) {
                    $result = intval($value);
                }
                break;
            case 'float':
            case 'double':
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
            default:
                $result = $value;
                break;
        }

        return $result;
    }

    public function parseFromDatabase($column)
    {
        $matches = array();
        preg_match_all('/^([a-zA-Z]+)(\(([^\(\)]+)\))?\s*(unsigned)?$/', $column['type'], $matches);

        if ($column['type'] === 'tinyint(1)') {
            $type = 'boolean';
        } else {
            $type = strtolower($matches[1][0]);
            foreach (static::$TYPES as $key => $columnType) {
                if ($columnType['name'] == $type) {
                    $type = $key;
                    break;
                }
            }
        }

        if (!array_key_exists($type, static::$TYPES)) {
            throw new UndefinedColumnTypeException("Undefined column type: {$type}");
        }

        $result = array('id' => $column['field'], 'type' => $type);
        if (in_array($type, self::$_disableSetDefaultValueTypes)) {
            return $result;
        }

        if (array_key_exists('default', $column)) {
            $default = $this->parseValue($column['default'], $type);
            if ($default !== null) {
                $result['default'] = $default;
            }
        }

        if ($type === 'enum') {
            $result['items'] = array_map(
                function($item) { return trim($item, "'"); },
                explode(',', $matches[3][0])
            );
            return $result;
        }

        if ($type !== 'boolean') {
            $result['length'] = intval($matches[3][0]);
        }

        if (in_array($type, self::$_abilitySetAutoIncrementTypes)) {
            if (array_key_exists('extra', $column) && $column['extra'] === 'auto_increment') {
                $result['auto_increment'] = true;
            }
        }

        if (in_array($type, self::$_abilitySetUnsignedTypes)) {
            if (!empty($matches[4][0]) && $matches[4][0] === 'unsigned') {
                $result['unsigned'] = true;
            }
        }

        return $result;
    }

    public function parseToDatabase($column)
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
            if (in_array($column['type'], self::$_abilitySetLengthTypes)) {
                if (isset($column['length']) && preg_match('/^-?\d+$/', $column['length'])) {
                    $length = $column['length'];
                } else {
                    $length = static::$TYPES[$column['type']]['length'];
                }
                $type = "{$type}({$length})";
            } else if ($column['type'] === 'enum') {
                if (empty($column['items'])) {
                    throw new ColumnException(
                        "Please set items for enum field `{$column['id']}`"
                    );
                }
                $type = $type . "('" . implode("','", $column['items']) . "')";
            }
        }

        if (in_array($column['type'], self::$_abilitySetUnsignedTypes)) {
            if (array_key_exists('unsigned', $column) && $column['unsigned'] === true) {
                $type = "{$type} unsigned";
            }
        }

        $result = array('field' => $column['id'], 'type' => $type);

        if (in_array($column['type'], self::$_abilitySetAutoIncrementTypes)) {
            if (array_key_exists('auto_increment', $column) && $column['auto_increment'] === true) {
                $result['extra'] = 'auto_increment';
                return $result;
            }
        }

        if(!array_key_exists('default', $column)) {
            return $result;
        }

        if (in_array($column['type'], self::$_disableSetDefaultValueTypes)) {
            return $result;
        }

        $default = $this->parseValue($column['default'], $column['type']);
        if ($default === null) {
            return $result;
        }

        if ($column['type'] === 'enum') {
            if (!in_array($default, $column['items'])) {
                throw new ColumnException(
                    "Error default value {$default} for enum field `{$column['id']}`"
                );
            }
        } else if ($column['type'] === 'boolean') {
            $default = ($default ? 1 : 0);
        }
        $result['default'] = $default;

        return $result;
    }

    public function get($table, $column = null)
    {
        static $cache = array();
        if (!empty($cache[$table])) {
           $columns = $cache[$table];
        } else {
           $columns = Connection::instance()->fetch("desc {$table}");
           $tmp = array();
           foreach ($columns as $item) {
                $_column = array();
                foreach ($item as $key => $value) {
                    $_column[strtolower($key)] = $value;
                }
                $tmp[$_column['field']] = $this->parseFromDatabase($_column);
           }
           $columns = $tmp;
           $cache[$table] = $columns;
        }

        if ($column === null) {
            return $columns;
        }

        return (isset($columns[$column]) ? $columns[$column] : null);
    }

    public function add($table, $column, $type, $options = array())
    {
        $options['type'] = $type;
        $options = $this->parseToDatabase($options);

        $sql = "ALTER TABLE `{$table}` ADD `{$column}` {$options['type']}";
        if (array_key_exists('extra', $options) && $options['extra'] === 'auto_increment') {
            $sql = "{$sql} AUTO_INCREMENT PRIMARY KEY";
        } else if (isset($options['default'])) {
            $sql = "{$sql} DEFAULT '{$options['default']}'";
        }

        return Connection::instance()->execute($sql);
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
        $options = $this->parseToDatabase($options);

        $sql = "ALTER TABLE `{$table}` CHANGE `{$column}` `{$newColumn}` {$options['type']}";
        if (array_key_exists('extra', $options) && $options['extra'] === 'auto_increment') {
            $sql = "{$sql} DEFAULT NULL AUTO_INCREMENT";
        } else if (isset($options['default'])) {
            $sql = "{$sql} DEFAULT '{$options['default']}'";
        }

        return Connection::instance()->execute($sql);
    }

    public function remove($table, $column)
    {
        return Connection::instance()->execute("ALTER TABLE `{$table}` DROP `{$column}`");
    }
}