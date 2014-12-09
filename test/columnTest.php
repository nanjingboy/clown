<?php
use \Clown\Cache;
use \Clown\Column;
use \Clown\Db;

class ColumnTest extends PHPUnit_Framework_TestCase
{
    private static $_column = null;
    private static $_cacheKey = null;

    public static function setUpBeforeClass()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS `column_test` (
              `id` int(11) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ";
        Db::instance()->execute($sql);
        self::$_column = Column::instance();
        self::$_cacheKey = self::$_column->cacheKey('column_test');
    }

    public function testAdd()
    {
        $this->assertTrue(
            self::$_column->add('column_test', 'test_add', 'integer')
        );
        $this->assertFalse(Cache::get(self::$_cacheKey));

        $this->assertEquals(
            array(
                'id' => 'test_add',
                'default' => null,
                'type' => 'integer',
                'length' => 11
            ),
            self::$_column->get('column_test', 'test_add')
        );
    }

    public function testRename()
    {
        $this->assertTrue(
            self::$_column->rename('column_test', 'test_add', 'test_update')
        );
        $this->assertFalse(Cache::get(self::$_cacheKey));

        $this->assertEquals(
            array(
                'id' => 'test_update',
                'default' => null,
                'type' => 'integer',
                'length' => 11
            ),
            self::$_column->get('column_test', 'test_update')
        );
    }

    public function testUpdate()
    {
        $this->assertTrue(
            self::$_column->update(
                'column_test',
                'test_update',
                'string',
                array(
                    'default' => 'hello world',
                    'length' => 40
                )
            )
        );
        $this->assertFalse(Cache::get(self::$_cacheKey));

        $this->assertEquals(
            array(
                'id' => 'test_update',
                'default' => 'hello world',
                'type' => 'string',
                'length' => 40
            ),
            self::$_column->get('column_test', 'test_update')
        );
    }

    public function testParseOptionsFromDatabase()
    {
        $this->assertEquals(
            array(
                'id' => 'id',
                'default' => null,
                'type' => 'integer',
                'length' => 11
            ),
            self::$_column->parseOptionsFromDatabase(
                array(
                    'field' => 'id',
                    'type' => 'int(11)',
                    'key' => 'PRI',
                    'default' => null,
                    'extra' => ''
                )
            )
        );
    }

    public function testParseOptionsToDatabase()
    {
        $this->assertEquals(
            array('type' => 'int(11)'),
            self::$_column->parseOptionsToDatabase(array('type' => 'integer'))
        );

        $this->assertEquals(
            array('type' => 'varchar(20)'),
            self::$_column->parseOptionsToDatabase(
                array('type' => 'string', 'length' => 20)
            )
        );

        $this->assertEquals(
            array('type' => 'datetime'),
            self::$_column->parseOptionsToDatabase(
                array(
                    'type' => 'datetime',
                    'length' => 40,
                    'default' => 'errorDatetime'
                )
            )
        );
    }

    public static function tearDownAfterClass()
    {
        Db::instance()->execute('drop table column_test');
        Cache::delete(self::$_cacheKey);
    }
}