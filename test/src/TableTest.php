<?php
namespace Test\Src;

use Clown\Table;
use Clown\Connection;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    private static $_table;
    private static $_testTables;

    public static function setUpBeforeClass()
    {
        self::$_table = Table::instance();

    }

    public function testCreate()
    {
        $this->assertTrue(
            self::$_table->create('users', function($table) {
                $table->integer('id', array('auto_increment' => true));
                $table->integer('age', array('unsigned' => true));
                $table->string('name', array('length' => 40));
                $table->timestamps();
                $table->index('name');
            })
        );

        $sql = '
            CREATE TABLE `users` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `age` int(11) unsigned DEFAULT NULL,
                `name` varchar(40) DEFAULT NULL,
                `created_at` datetime DEFAULT NULL,
                `updated_at` datetime DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `index_name` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        ';
        $this->assertEquals(
            preg_replace('/\s/', '', $sql),
            preg_replace(
                '/\s/',
                '',
                Connection::instance()->fetch(
                    'show create table users'
                )[0]['Create Table']
            )
        );
    }

    public static function tearDownAfterClass()
    {
        self::$_table->remove('users');
    }
}