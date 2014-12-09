<?php
use \Clown\Schema;
use \Clown\UndefinedMethodException;

class SchemaTest extends PHPUnit_Framework_TestCase
{
    public function testParseMethod()
    {
        $this->assertEquals(
            array('class' => 'Table', 'method' => 'create'),
            Schema::parseMethod('createTable')
        );
        $this->assertEquals(
           array('class' => 'Column', 'method' => 'rename'),
           Schema::parseMethod('renameColumn')
        );
        $this->assertEquals(
           array('class' => 'Index', 'method' => 'get'),
           Schema::parseMethod('getIndex')
        );

        try {
          Schema::parseMethod('callUndefinedMethod');
        } catch (UndefinedMethodException $expected) {
          return;
        }

        throw new UndefinedMethodException('callUndefinedMethod', 'Schema');
    }
}