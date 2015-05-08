<?php
namespace Clown;

class Table extends Singleton
{
    public function create($table, $closure = null)
    {
        $sql = array();
        $this->columns = array();
        $this->indexes = array();
        if ($closure !== null && is_callable($closure)) {
            $closure($this);
            $hasSetPrimary = false;
            foreach ($this->columns as $column) {
                if (array_key_exists('extra', $column) && $column['extra'] === 'auto_increment') {
                    $hasSetPrimary = true;
                    array_push($sql, "`{$column['field']}` {$column['type']} AUTO_INCREMENT PRIMARY KEY");
                } else if (isset($column['default'])) {
                    array_push($sql, "`{$column['field']}` {$column['type']} DEFAULT '{$column['default']}'");
                } else {
                    array_push($sql, "`{$column['field']}` {$column['type']}");
                }
            }

            foreach ($this->indexes as $index) {
                if ($index['name'] === 'PRIMARY') {
                    if ($hasSetPrimary === false) {
                        array_push($sql, "PRIMARY KEY ({$index['keys']})");
                    }
                } else if (strpos($index['name'], 'unique_index_') === 0) {
                    array_push($sql, "UNIQUE KEY `{$index['name']}`({$index['keys']})");
                } else {
                    array_push($sql, "KEY `{$index['name']}`({$index['keys']})");
                }
            }
        }

        $sql = implode(",\n", $sql);
        return Connection::instance()->execute(
            "CREATE TABLE IF NOT EXISTS `{$table}` ({$sql}) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
        );
    }

    public function rename($oldTable, $newTable)
    {
        return Connection::instance()->execute("RENAME TABLE `{$oldTable}` TO `{$newTable}`");
    }

    public function remove($table)
    {
        return Connection::instance()->execute("DROP TABLE `{$table}`");
    }

    public function __call($method, $arguments)
    {
        if ($method === 'index' || array_key_exists($method, Column::$TYPES)) {
            if (count($arguments) <= 0) {
                throw new MissingArgumentException($method, __CLASS__);
            }
            $column = $arguments[0];
            $options = (!empty($arguments[1]) ? $arguments[1] : array());
            if ($method === 'index') {
                if (!is_array($column)) {
                    $column = array($column);
                }

                $this->indexes = array_merge(
                    $this->indexes,
                    array(
                        array(
                            'name' => Index::instance()->getName($column, $options),
                            'keys' => '`' . implode('`,`', $column) . '`'
                        )
                    )
                );
            } else {
                $options['id'] = $column;
                $options['type'] = $method;
                $this->columns = array_merge(
                    $this->columns,
                    array(
                        Column::instance()->parseToDatabase($options)
                    )
                );
            }
        } else if ($method === 'timestamps') {
            $this->columns = array_merge(
                $this->columns,
                array(
                    Column::instance()->parseToDatabase(
                        array('id' => 'created_at', 'type' => 'datetime')
                    )
                )
            );
            $this->columns = array_merge(
                $this->columns,
                array(
                    Column::instance()->parseToDatabase(
                        array('id' => 'updated_at', 'type' => 'datetime')
                    )
                )
            );
        } else {
            throw new UndefinedMethodException($method, __CLASS__);
        }
    }
}