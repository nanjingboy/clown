<?php
namespace Clown;

use Exception;

class Table extends Singleton
{
    public function create($table, $closure = null)
    {
        $this->tableId = $table;
        $this->columns = array();
        $this->indexes = array();
        try {
            $sql = array('`id` int(11) NOT NULL PRIMARY KEY auto_increment');
            if ($closure !== null && is_callable($closure)) {
                $closure($this);
                foreach ($this->columns as $column) {
                    $id = $column[0];
                    $options = $column[1];
                    if (isset($options['default'])) {
                        array_push($sql, "`{$id}` {$options['type']} DEFAULT '{$options['default']}'");
                    } else {
                        array_push($sql, "`{$id}` {$options['type']}");
                    }
                }

                foreach ($this->indexes as $index) {
                    if ($index['unique']) {
                        array_push($sql, "UNIQUE KEY `{$index['name']}`({$index['index']})");
                    } else {
                        array_push($sql, "KEY `{$index['name']}`({$index['index']})");
                    }
                }
            }
            $sql = implode(",\n", $sql);

            return Db::instance()->execute(
                "CREATE TABLE `{$table}` ({$sql}) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
            );
        } catch(Exception $e) {
            throw new ClownException($e);
        }
    }

    public function rename($oldTable, $newTable)
    {
        try {
            if (Db::instance()->execute("RENAME TABLE {$oldTable} TO {$newTable}")) {
                $caches = Cache::get(Column::instance()->cacheKey($oldTable));
                if (!empty($caches)) {
                    Cache::delete(Column::instance()->cacheKey($oldTable));
                    Cache::set(Column::instance()->cacheKey($newTable), $caches);
                }
                return true;
            }
            return false;
        } catch(Exception $e) {
            throw new ClownException($e);
        }
    }

    public function remove($table)
    {
        try {
            if (Db::instance()->execute("DROP TABLE {$table}")) {
                Cache::delete(Column::instance()->cacheKey($table));
                return true;
            }
            return false;
        } catch (Exception $e) {
            throw new ClownException($e);
        }
    }

    public function __call($method, $arguments)
    {
        if ($method === 'index' || array_key_exists($method, Column::$TYPES)) {
            if (count($arguments) <= 0) {
                throw new MissingArgumentException($method, __CLASS__);
            }
            $column = $arguments[0];
            $options = (isset($arguments[1]) ? $arguments[1] : array());
            if ($method === 'index') {
                array_push(
                    $this->indexes,
                    Index::instance()->parse($column, $options)
                );
            } else {
                $options['type'] = $method;
                array_push(
                    $this->columns,
                    array(
                        $column,
                        Column::instance()->parseOptionsToDatabase($options)
                    )
                );
            }
        } else if ($method === 'timestamps') {
            array_push(
                $this->columns,
                array(
                    'created_at',
                    Column::instance()->parseOptionsToDatabase(
                        array('type' => 'datetime')
                    )
                )
            );
            array_push(
                $this->columns,
                array(
                    'updated_at',
                    Column::instance()->parseOptionsToDatabase(
                        array('type' => 'datetime')
                    )
                )
            );
        } else {
            throw new UndefinedMethodException($method, __CLASS__);
        }
    }
}