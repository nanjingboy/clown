<?php
namespace Test\Src;

use Clown\SqlBuilder;
use PHPUnit_Framework_TestCase;

class SqlBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testParseConditions()
    {
        $this->assertEquals(
            array(
                'sql' => 'age > 10',
                'values' => array()
            ),
            SqlBuilder::parseConditions(array('age > 10'))
        );

        $this->assertEquals(
            array(
                'sql' => 'age > ?',
                'values' => array(10)
            ),
            SqlBuilder::parseConditions(array('age > ?', 10))
        );

        $this->assertEquals(
            array(
                'sql' => 'id in (?) or country = ?',
                'values' => array(array(1, 2), 'China')
            ),
            SqlBuilder::parseConditions(
                array(
                    'id in (?) or country = ?',
                    array(1, 2), 'China'
                )
            )
        );

        $this->assertEquals(
            array(
                'sql' => '`id` in (?) and `country` = ?',
                'values' => array(array(1, 2), 'China')
            ),
            SqlBuilder::parseConditions(
                array('id' => array(1, 2), 'country' => 'China')
            )
        );
    }

    public function testParseQuerySql()
    {
        $this->assertEquals(
            array(
                'sql' => 'SELECT * FROM `user`',
                'values' => array()
            ),
            SqlBuilder::parseQuerySql('user')
        );

        $this->assertEquals(
            array(
                'sql' => 'SELECT id,concat(first_name, family_name) as fullname FROM `user` WHERE `id` in (?) GROUP BY sex HAVING sex = ? ORDER BY id DESC LIMIT 0,10',
                'values' => array(array(1, 2), 'male')
            ),
            SqlBuilder::parseQuerySql(
                'user',
                array(
                    'select' => array('id', 'concat(first_name, family_name) as fullname'),
                    'conditions' => array('id' => array(1, 2)),
                    'group' => 'sex',
                    'having' => array('sex = ?', 'male'),
                    'order' => 'id DESC',
                    'limit' => 10
                )
            )
        );
    }

    public function testParseInsertSql()
    {
        $this->assertEquals(
            array(
                'sql' => 'INSERT INTO `user` SET `first_name`=?,`family_name`=?,`age`=?',
                'values' => array('Tom', 'Huang', 24)
            ),
            SqlBuilder::parseInsertSql(
                'user',
                array(
                    'first_name' => 'Tom',
                    'family_name' => 'Huang',
                    'age' => 24
                )
            )
        );
    }

    public function testParseUpdateSql()
    {
        $this->assertEquals(
            array(
                'sql' => 'UPDATE `user` SET `age`=? WHERE `first_name` = ?',
                'values' => array(25, 'Tom')
            ),
            SqlBuilder::parseUpdateSql(
                'user',
                array('age' => 25),
                array('first_name' => 'Tom')
            )
        );
    }

    public function testParseDestroySql()
    {
        $this->assertEquals(
            array(
                'sql' => 'DELETE FROM `user`',
                'values' => array()
            ),
            SqlBuilder::parseDestroySql('user')
        );

        $this->assertEquals(
            array(
                'sql' => 'DELETE FROM `user` WHERE id = ? OR first_name = ?',
                'values' => array(1, 'Tom')
            ),
            SqlBuilder::parseDestroySql(
                'user',
                array('id = ? OR first_name = ?', 1, 'Tom')
            )
        );
    }
}