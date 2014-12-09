<?php
use \Clown\Schema;
use \Models\Validate;

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Schema::createTable('validates', function($table) {
            $table->string('presence');

            $table->string('size_is');
            $table->string('size_in');
            $table->string('size_minimum');
            $table->string('size_maximum');

            $table->string('inclusion');
            $table->string('exclusion');
            $table->string('format');

            $table->float('onlyInteger');
            $table->integer('even');
            $table->integer('odd');
            $table->integer('greater');
            $table->integer('greaterOrEqual');
            $table->float('equal');
            $table->float('less');
            $table->float('lessOrEqual');

            $table->string('unique');
        });
    }

    public function testErrors()
    {
        $model = new Validate();

        $model->sizeIs = 'hell';
        $model->sizeIn = 'hello world';
        $model->sizeMinimum = 'hello';
        $model->sizeMaximum = 'I hate PHP and Java';

        $model->unique = 'name';
        $model->format = '24:00:00';
        $model->inclusion = 'python';
        $model->exclusion = 'php';

        $model->onlyInteger = 1.2;
        $model->even = 1;
        $model->odd = 2;
        $model->greater = 1;
        $model->greaterOrEqual = 0;
        $model->equal = 0;
        $model->less = 1;
        $model->lessOrEqual = 2;

        $this->assertFalse($model->save(true));
        $this->assertFalse($model->isValid());

        $this->assertEquals(
            array(
                'presence' => array('can not be blank', 'should equals to test'),
                'size_is' => array('should be exactly 5 characters long'),
                'size_in' => array('should be above 4 and below 10 characters long'),
                'size_minimum' => array('should not be below 9 characters long'),
                'size_maximum' => array('should not be above 15 characters long'),
                'inclusion' => array('should be a value within ruby,erlang'),
                'exclusion' => array('should not be a value within php,java'),
                'format' => array('24:00:00 can not match the format'),
                'onlyInteger' => array('1.2 is not an integer'),
                'even' => array('must be even'),
                'odd' => array('must be odd'),
                'greater' => array('must be greater than 1'),
                'greaterOrEqual' => array('must be greater than or equal to 1'),
                'equal' => array('must be equal to 1'),
                'less' => array('must be less than 1'),
                'lessOrEqual' => array('must be less than or equal to 1')
            ),
            $model->errors()
        );
    }

    public function testValidation()
    {
        Schema::removeTable('validates');
    }
}