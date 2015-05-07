<?php
namespace Test\Lib\ActiveRecord;

use Clown\Connection;
use PHPUnit_Framework_TestCase;

class ConnectionTest extends PHPUnit_Framework_TestCase
{
    public function testPrepare()
    {
        $connection = Connection::instance();
        $this->assertEquals(
            array(
                'sql' => 'select * from users where age > 10',
                'values' => array()
            ),
            $connection->prepare(
                'select * from users where age > 10',
                array()
            )
        );

        $this->assertEquals(
            array(
                'sql' => 'select * from users where age > ?',
                'values' => array(10)
            ),
            $connection->prepare(
                'select * from users where age > ?',
                array(10)
            )
        );

        $this->assertEquals(
            array(
                'sql' => 'select * from users where id in (?,?) or (country = ? and city not in (?,?,?))',
                'values' => array(1, 2, 'China', 'ShangHai', 'GuangZhou', 'BeiJing')
            ),
            $connection->prepare(
                'select * from users where id in (?) or (country = ? and city not in (?))',
                array(
                    array(1, 2),
                    'China',
                    array('ShangHai', 'GuangZhou', 'BeiJing')
                )
            )
        );
    }
}