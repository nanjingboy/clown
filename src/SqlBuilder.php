<?php
namespace Clown;

class SqlBuilder
{
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
                        array_push($tmp[0], "`{$key}` in (?)");
                    } else {
                        array_push($tmp[0], "`{$key}` = ?");
                    }
                    array_push($tmp, $value);
                }
                $tmp[0] = implode(' and ', $tmp[0]);
                $conditions = $tmp;
            }

            return array('sql' => array_shift($conditions), 'values' => $conditions);
        }

        return array('sql' => null, 'values' => array());
    }

    public static function parseQuerySql($table, $options = array())
    {
        if (empty($options['select'])) {
            $select = '*';
        } else if (is_array($options['select'])) {
            //因select属性中可能出现类似count(id)查询字段，所以此处对字段不做`处理
            $select = implode(',', $options['select']);
        } else {
            $select = $options['select'];
        }

        $sql = "SELECT {$select} FROM `{$table}`";
        $conditions = array('sql' => null, 'values' => array());
        if (!empty($options['conditions'])) {
            $conditions = static::parseConditions($options['conditions']);
            $sql .= " WHERE {$conditions['sql']}";
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
        }

        if (isset($options['limit']) && preg_match('/^\d+$/', $options['limit'])) {
            if (!isset($options['offset']) || !preg_match('/^\d+$/', $options['offset'])) {
                $options['offset'] = 0;
            }
            $sql .= " LIMIT {$options['offset']},{$options['limit']}";
        }

        return array('sql' => $sql, 'values' => $conditions['values']);
    }

    public static function parseInsertSql($table, $attributes)
    {
        $setting = '`' . implode('`=?,`', array_keys($attributes)) . '`=?';
        return array(
            'sql' => "INSERT INTO `{$table}` SET {$setting}",
            'values' => array_values($attributes)
        );
    }

    public static function parseUpdateSql($table, $attributes, $conditions = array())
    {
        $setting = '`' . implode('`=?,`', array_keys($attributes)) . '`=?';
        $sql = "UPDATE `{$table}` SET {$setting}";
        $values = array_values($attributes);
        if (!empty($conditions)) {
            $conditions = static::parseConditions($conditions);
            $sql .= ' WHERE ' . $conditions['sql'];
            $values = array_merge($values, $conditions['values']);
        }

        return array('sql' => $sql, 'values' => $values);
    }

    public static function parseDestroySql($table, $conditions = array())
    {
        $sql = "DELETE FROM `$table`";
        $values = array();
        if (!empty($conditions)) {
            $conditions = static::parseConditions($conditions);
            $sql .= ' WHERE ' . $conditions['sql'];
            $values = $conditions['values'];
        }

        return array('sql' => $sql, 'values' => $values);
    }
}