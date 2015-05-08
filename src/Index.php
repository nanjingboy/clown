<?php
namespace Clown;

class Index extends Singleton
{
    public function getName($columns, $options = array())
    {
        if (array_key_exists('primary', $options) && $options['primary'] === true) {
            return 'PRIMARY';
        }

        if (array_key_exists('unique', $options) && $options['unique'] === true) {
            return 'unique_index_' . implode('_and_', $columns);
        }

        return 'index_' . implode('_and_', $columns);
    }

    public function add($table, $columns, $options = array())
    {
        $name = $this->getName($columns, $options);
        $keys = '`' . implode('`,`', $columns) . '`';
        if ($name === 'PRIMARY') {
            $sql = "ALTER TABLE `{$table}` ADD PRIMARY KEY ({$keys})";
        } else if (strpos($name, 'unique_index_') === 0) {
            $sql = "CREATE UNIQUE INDEX `{$name}` ON `{$table}` ({$keys})";
        } else {
            $sql = "CREATE INDEX `{$name}` ON `{$table}` ({$keys})";
        }

        return Connection::instance()->execute($sql);
    }

    public function remove($table, $columns, $options = array())
    {
        $name = $this->getName($columns, $options);
        if ($name === 'PRIMARY') {
            $sql = "ALTER TABLE `{$table}` DROP PRIMARY KEY";
        } else {
            $sql = "ALTER TABLE `{$table}` DROP INDEX `{$name}`";
        }

        return Connection::instance()->execute($sql);
    }
}