<?php
namespace Clown;

use Exception;

class Index extends Singleton
{
    private function _getIndexName($columns)
    {
        return 'index_' . implode('_and_', $columns);
    }

    public function parse($columns, $options = array())
    {
        $indexName = $this->_getIndexName($columns);

        if (!empty($options['length'])) {
            foreach ($columns as $index => $column) {
                if (isset($options['length'][$column]) && preg_match('/^\d+$/', $options['length'][$column])) {
                    $columns[$index] = "{$column}({$options['length'][$column]})";
                }
            }
        }

        return array(
            'unique' => isset($options['unique']) && $options['unique'] === true,
            'name' => $indexName,
            'index' => implode(',', $columns)
        );
    }

    public function add($table, $columns, $options = array())
    {
        $options = $this->parse($columns, $options);
        if ($options['unique']) {
            $sql = "CREATE UNIQUE INDEX `{$options['name']}` ON `{$table}`({$options['index']})";
        } else {
            $sql = "CREATE INDEX `{$options['name']}` ON `{$table}`({$options['index']})";
        }

        return Db::instance()->save($sql);
    }

    public function remove($table, $columns)
    {
        $indexName = $this->_getIndexName($columns);
        return Db::instance()->save("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
    }
}