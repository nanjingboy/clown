<?php
namespace Test\Src;

use Clown\Callback;
use Clown\Validation;
use PHPUnit_Framework_TestCase;

class ValidationModel
{
    public static $validates_presence_of = array(
        array('presence')
    );

    public static $validates_size_of = array(
        array('size_5', 'is' => 5),
        array('size_between_5_and_9', 'in' => array(5, 9)),
        array('size_minimum_9', 'minimum' => 9),
        array('size_maximum_15', 'maximum' => 15)
    );

    public static $validates_inclusion_of = array(
        array('inclusion', 'in' => array('ruby', 'erlang'))
    );

    public static $validates_exclusion_of = array(
        array('exclusion', 'in' => array('php', 'java'))
    );

    public static $validates_format_of = array(
        array('format', 'with' => '/^[0-2][0-3]:[0-5][0-9]:[0-5][0-9]$/')
    );

    public static $validates_numericality_of = array(
        array('only_integer', 'only_integer' => true),
        array('even', 'even' => true),
        array('odd', 'odd' => true),
        array('greater', 'greater' => 1),
        array('greater_or_equal', 'greater_or_equal' => 1),
        array('equal', 'equal' => 1),
        array('less', 'less' => 1),
        array('less_or_equal', 'less_or_equal' => 1),
    );

    public static $validates_uniqueness_of = array(
        array('unique')
    );

    public static $validate = array(
        array('_presenceShouldEqualsToTest')
    );

    private function _presenceShouldEqualsToTest()
    {
        if ($this->presence === 'Test') {
            return true;
        }
        $this->validation->addError('presence', 'should equals to test');
    }

    public function __construct()
    {
        $this->id = null;
        $this->validation = new Validation($this);
        $this->callback = new Callback($this);
    }

    public function isNewRecord()
    {
        return true;
    }

    public function exists()
    {
        return $this->unique === 'unique';
    }
}

class ValidationTest extends PHPUnit_Framework_TestCase
{
    public function testValidate()
    {
        $model = new ValidationModel();
        $model->presence = null;
        $model->size_5 = 'hell';
        $model->size_between_5_and_9 = 'hello world';
        $model->size_minimum_9 = 'hello';
        $model->size_maximum_15 = 'PHP and Java Program Language';
        $model->format = '24:00:00';
        $model->inclusion = 'python';
        $model->exclusion = 'php';
        $model->only_integer = 1.2;
        $model->even = 1;
        $model->odd = 2;
        $model->greater = 1;
        $model->greater_or_equal = 0;
        $model->equal = 0;
        $model->less = 1;
        $model->less_or_equal = 2;
        $model->unique = 'unique';

        $this->assertFalse($model->validation->validate());
        $this->assertEquals(
            array(
                'presence' => array('can not be blank', 'should equals to test'),
                'size_5' => array('should be exactly 5 characters long'),
                'size_between_5_and_9' => array('should be above 4 and below 10 characters long'),
                'size_minimum_9' => array('should not be below 9 characters long'),
                'size_maximum_15' => array('should not be above 15 characters long'),
                'inclusion' => array('should be a value within ruby,erlang'),
                'exclusion' => array('should not be a value within php,java'),
                'format' => array('24:00:00 can not match the format'),
                'only_integer' => array('1.2 is not an integer'),
                'even' => array('must be even'),
                'odd' => array('must be odd'),
                'greater' => array('must be greater than 1'),
                'greater_or_equal' => array('must be greater than or equal to 1'),
                'equal' => array('must be equal to 1'),
                'less' => array('must be less than 1'),
                'less_or_equal' => array('must be less than or equal to 1'),
                'unique' => array('unique has been token')
            ),
            $model->validation->errors()
        );
    }
}