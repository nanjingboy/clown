<?php
namespace Clown;

use PDO;
use Exception;
use PDOException;

class Connection extends Singleton
{
    private static $_PDO_OPTIONS = array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false
    );

    private $_connection;
    private $_hasBeganTransaction = false;

    private function _prepare($sql, $values = array())
    {
        if (empty($values)) {
            return array('sql' => $sql, 'values' => $values);
        }

        $arrayValues = array_filter($values, 'is_array');
        $sql = preg_replace_callback(
            '/\s+in\s*\(\s*\?\s*\)/i',
            function($matches) use(&$arrayValues) {
                if (empty($arrayValues)) {
                    return ' = ?';
                }
                $values = array_shift($arrayValues);
                $valuesCount = count($values);
                if ($valuesCount === 1) {
                    return ' = ?';
                }

                return ' in (' . implode(',', array_fill(0, $valuesCount, '?')) . ')';
            },
            $sql
        );
        $_values = array();
        foreach ($values as $value) {
            if (is_array($value)) {
                $_values = array_merge($_values, $value);
            } else {
                array_push($_values, $value);
            }
        }

        return array('sql' => $sql, 'values' => $_values);
    }

    private function _execute($sql, $values = array())
    {
        try {
            $prepares = $this->_prepare($sql, $values);
            $stmt = $this->_connection->prepare($prepares['sql']);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute($prepares['values']);
            return $stmt;
        } catch (PDOException $e) {
            throw new ConnectionException($e);
        }
    }

    public function __construct()
    {
        $config = Config::instance()->get('server');
        try {
            $this->_connection = new PDO(
                "mysql:dbname={$config['database']};host={$config['host']};port={$config['port']};charset=utf8",
                $config['user'],
                $config['password'],
                self::$_PDO_OPTIONS
            );
        } catch (PDOException $e) {
            throw new ConnectionException($e);
        }
    }

    public function fetch($sql, $values = array())
    {
        return $this->_execute($sql, $values)->fetchAll();
    }

    /**
     * Get the first column of result as array
     */
    public function fetchColumn($sql, $values = array())
    {
        return $this->_execute($sql, $values)->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get the first row of result as array
     */
    public function fetchRow($sql, $values = array())
    {
        return $this->_execute($sql, $values)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the first field of first column of result
     */
    public function fetchScalar($sql, $values = array())
    {
        return $this->_execute($sql, $values)->fetchColumn();
    }

    public function insert($sql, $values = array())
    {
        $this->_execute($sql, $values);
        return $this->_connection->lastInsertId();
    }

    public function execute($sql, $values = array())
    {
        $this->_execute($sql, $values);
        return true;
    }

    public function update($sql, $values = array())
    {
        return $this->execute($sql, $values);
    }

    public function beginTransaction()
    {
        if ($this->_hasBeganTransaction) {
            return true;
        }

        $this->_hasBeganTransaction = true;
        $this->_connection->beginTransaction();
    }

    public function rollback()
    {
        if ($this->_hasBeganTransaction) {
            $this->_hasBeganTransaction = false;
            $this->_connection->rollback();
        }

        return true;
    }

    public function commit()
    {
        if ($this->_hasBeganTransaction) {
            $this->_hasBeganTransaction = false;
            $this->_connection->commit();
        }

        return true;
    }

    public function transaction($closure)
    {
        if ($this->_hasBeganTransaction) {
            return $closure();
        }

        try {
            $this->beginTransaction();
            if ($closure() === false) {
                $this->rollback();
                return false;
            } else {
                $this->commit();
            }
        } catch (Exception $e) {
            $this->rollback();
            throw new ClownException($e);
        }
        return true;
    }
}