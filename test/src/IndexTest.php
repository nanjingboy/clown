<?php
namespace Test\Src;

use Clown\Index;
use PHPUnit_Framework_TestCase;

class IndexTest extends PHPUnit_Framework_TestCase
{
    private static $_index;

    public static function setUpBeforeClass()
    {
        self::$_index = Index::instance();
    }

    public function testIndexes()
    {
        $this->assertEquals(
            array(
                'PRIMARY' => array(
                    'columns' => array('id', 'age'),
                    'options' => array('primary' => true)
                ),
                'unique_index_name_and_address' => array(
                    'columns' => array('name', 'address'),
                    'options' => array('unique' => true)
                ),
                'index_name' => array(
                    'columns' => array('name'),
                    'options' => array()
                )
            ),
            self::$_index->parseFromDatabase(
                array(
                    array(
                        'non_unique' => '0',
                        'key_name' => 'PRIMARY',
                        'column_name' => 'id'
                    ),
                    array(
                        'non_unique' => '0',
                        'key_name' => 'PRIMARY',
                        'column_name' => 'age'
                    ),
                    array(
                        'non_unique' => '0',
                        'key_name' => 'unique_index_name_and_address',
                        'column_name' => 'name'
                    ),
                    array(
                        'non_unique' => '0',
                        'key_name' => 'unique_index_name_and_address',
                        'column_name' => 'address'
                    ),
                    array(
                        'non_unique' => '1',
                        'key_name' => 'index_name',
                        'column_name' => 'name'
                    )
                )
            )
        );
    }
}