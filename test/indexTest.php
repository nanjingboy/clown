<?php
use \Clown\Index;
use \Clown\Db;

class IndexTest extends PHPUnit_Framework_TestCase
{
    private static $_index = null;

    public static function setUpBeforeClass()
    {
        self::$_index = Index::instance();
    }

    public function testParse()
    {
        $this->assertEquals(
            array(
                'unique' => false,
                'name' => 'index_name_and_age',
                'index' => 'name(10),age'
            ),
            self::$_index->parse(
                array('name', 'age'),
                array('length' => array('name' => 10))
            )
        );

        $this->assertEquals(
            array(
                'unique' => true,
                'name' => 'index_name_and_age',
                'index' => 'name,age'
            ),
            self::$_index->parse(
                array('name', 'age'),
                array('unique' => true)
            )
        );
    }
}