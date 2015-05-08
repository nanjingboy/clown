<?php
namespace Test\Src;

use Clown\Column;
use PHPUnit_Framework_TestCase;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    private static $_column;

    public static function setUpBeforeClass()
    {
        self::$_column = Column::instance();
    }

    public function testParseFromDatabase()
    {
        $this->assertEquals(
            array(
                'id' => 'integer_field',
                'type' => 'integer',
                'length' => 11,
                'auto_increment' => true
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'integer_field',
                    'type' => 'int(11)',
                    'default' => null,
                    'extra' => 'auto_increment'
                )
            )
        );

        $this->assertEquals(
            array(
                'id' => 'unsigned_integer_field',
                'type' => 'integer',
                'length' => 11,
                'unsigned' => true,
                'auto_increment' => true
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'unsigned_integer_field',
                    'type' => 'int(11) unsigned',
                    'default' => null,
                    'extra' => 'auto_increment'
                )
            )
        );

        $this->assertEquals(
            array(
                'id' => 'tinyint_field',
                'type' => 'tinyint',
                'default' => 1,
                'length' => 4
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'tinyint_field',
                    'type' => 'tinyint(4)',
                    'default' => 1
                )
            )
        );

        $this->assertEquals(
            array(
                'id' => 'boolean_field',
                'type' => 'boolean',
                'default' => false
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'boolean_field',
                    'type' => 'tinyint(1)',
                    'default' => 0
                )
            )
        );

        $this->assertEquals(
            array(
                'id' => 'enum_field',
                'type' => 'enum',
                'default' => 'false',
                'items' => array('false', 'true')
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'enum_field',
                    'type' =>  "enum('false','true')",
                    'default' => 'false'
                )
            )
        );

        $this->assertEquals(
            array(
                'id' => 'blob_field',
                'type' => 'binary'
            ),
            self::$_column->parseFromDatabase(
                array(
                    'field' => 'blob_field',
                    'type' => 'blob'
                )
            )
        );
    }

    public function testParseToDatabase()
    {
        $this->assertEquals(
            array(
                'field' => 'integer_field',
                'type' => 'int(11)',
                'extra' => 'auto_increment'
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'integer_field',
                    'type' => 'integer',
                    'length' => 11,
                    'auto_increment' => true
                )
            )
        );

        $this->assertEquals(
             array(
                'field' => 'unsigned_integer_field',
                'type' => 'int(11) unsigned',
                'extra' => 'auto_increment'
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'unsigned_integer_field',
                    'type' => 'integer',
                    'length' => 11,
                    'unsigned' => true,
                    'auto_increment' => true
                )
            )
        );

        $this->assertEquals(
            array(
                'field' => 'tinyint_field',
                'type' => 'tinyint(4)',
                'default' => 1
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'tinyint_field',
                    'type' => 'tinyint',
                    'default' => 1,
                    'length' => 4
                )
            )
        );

        $this->assertEquals(
            array(
                'field' => 'boolean_field',
                'type' => 'tinyint(1)',
                'default' => 0
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'boolean_field',
                    'type' => 'boolean',
                    'default' => false
                )
            )
        );

        $this->assertEquals(
            array(
                'field' => 'enum_field',
                'type' =>  "enum('false','true')",
                'default' => 'false'
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'enum_field',
                    'type' => 'enum',
                    'default' => 'false',
                    'items' => array('false', 'true')
                )
            )
        );

        $this->assertEquals(
            array(
                'field' => 'blob_field',
                'type' => 'blob'
            ),
            self::$_column->parseToDatabase(
                array(
                    'id' => 'blob_field',
                    'type' => 'binary'
                )
            )
        );
    }
}