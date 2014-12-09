<?php
use \Clown\Callback;

class Model
{
    public static $beforeInsert = array('init');

    public $result = array();

    public function init()
    {
        array_push($this->result, 'init');
    }

    public function register()
    {
        array_push($this->result, 'register');
    }

    public function beforeSave()
    {
        array_push($this->result, 'save');
    }

    public function afterSave()
    {
    }
}

class CallbackTest extends PHPUnit_Framework_TestCase
{
    private static $_model;
    private static $_callback;

    public static function setUpBeforeClass()
    {
        self::$_model = new Model();
        self::$_callback = new Callback(self::$_model);
    }

    public function testRegister()
    {
        self::$_callback->register('beforeInsert', 'register');

        $this->assertEquals(
            array('init', 'register'),
            self::$_callback->getCallbacks('beforeInsert')
        );

        $this->assertEquals(
            array('afterSave'),
            self::$_callback->getCallbacks('afterSave')
        );
    }

    public function testCall()
    {
        self::$_callback->register('beforeInsert', function($model) {
            array_push($model->result, 'closure');
        });
        self::$_callback->call('beforeInsert');
        $this->assertEquals(
            array('init', 'register', 'closure', 'save'),
            self::$_model->result
        );
    }
}