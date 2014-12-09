<?php
namespace Clown;

use PDO;
use Exception;
use PDOException;

class Db extends Singleton
{
    private static $_PDO_OPTIONS = array(
        PDO::ATTR_CASE => PDO::CASE_LOWER,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false
    );

    private $_connection;

    private function _execute($sql, $values)
    {
        try {
            $stmt = $this->_connection->prepare($sql);
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $stmt->execute($values);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException($e);
        }
    }

    public function __construct()
    {
        $config = Config::get('db');
        try {
            $this->_connection = new PDO(
                "mysql:dbname={$config['dbName']};host={$config['host']};port={$config['port']};charset=utf8",
                $config['user'],
                $config['password'],
                self::$_PDO_OPTIONS
            );
        } catch (PDOException $e) {
            throw new DatabaseException($e);
        }
    }

    public function execute($sql, $values = array())
    {
        $this->_execute($sql, $values);
        return true;
    }

    public function fetch($sql, $values = array())
    {
        $rows = array();
        $stmt = $this->_execute($sql, $values);
        while (($row = $stmt->fetch(PDO::FETCH_ASSOC))) {
            array_push($rows, $row);
        }
        return $rows;
    }

    public function insert($sql, $values = array())
    {
        $this->_execute($sql, $values);
        return $this->_connection->lastInsertId();
    }

    public function update($sql, $values = array())
    {
        return $this->execute($sql, $values);
    }

    public function translation($closure)
    {
        try {
            $this->_connection->beginTransaction();
            if ($closure() === false) {
                $this->_connection->rollback();
                return false;
            } else {
                $this->_connection->commit();
            }
        } catch (Exception $e) {
            $this->_connection->rollback();
            throw new ClownException($e);
        }
        return true;
    }
}