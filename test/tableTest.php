<?php
use \Clown\Table;
use \Clown\Db;

class TableTest extends PHPUnit_Framework_TestCase
{
    private static $_table = null;

    public static function setUpBeforeClass()
    {
        self::$_table = Table::instance();
    }

    public function testCreate()
    {
        $this->assertTrue(
            self::$_table->create('table_test', function($table) {
                $table->integer('age');
                $table->string('name', array('length' => 40));
                $table->timestamps();
                $table->index(array('name'));
            })
        );

        $sql = '
            CREATE TABLE `table_test` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `age` int(11) DEFAULT NULL,
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
                Db::instance()->fetch(
                    'show create table table_test'
                )[0]['create table']
            )
        );
    }

    public static function tearDownAfterClass()
    {
        self::$_table->remove('table_test');
    }
}