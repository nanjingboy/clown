<?php
namespace Test\Src;

use Clown\Callback;
use Clown\Reflection;
use PHPUnit_Framework_TestCase;

class CallbackModel
{
    public static $before_create = array('_init');

    public static $result = array();

    private function _init()
    {
        array_push(static::$result, 'init');
    }

    public static function before_save()
    {
        array_push(static::$result, 'save');
    }

    public function after_create()
    {
    }

    public function after_save()
    {
    }

    public function register()
    {
        array_push(static::$result, 'register');
    }

    public function before_validation()
    {
    }

    public function before_validation_on_create()
    {
    }
}

class CallbackTest extends PHPUnit_Framework_TestCase
{
    private static $_model;
    private static $_callback;

    public static function setUpBeforeClass()
    {
        self::$_model = new CallbackModel();
        self::$_callback = new Callback(self::$_model);
    }

    public function testGetCallbacks()
    {
        $this->assertEquals(
            array('_init', 'before_save'),
            Reflection::invokeInstanceMethod(
                self::$_callback,
                '_getCallbacks',
                array('before_create')
            )
        );

        $this->assertEquals(
            array('after_create', 'after_save'),
            Reflection::invokeInstanceMethod(
                self::$_callback,
                '_getCallbacks',
                array('after_create')
            )
        );

        $this->assertEquals(
            array('before_validation_on_create', 'before_validation'),
            Reflection::invokeInstanceMethod(
                self::$_callback,
                '_getCallbacks',
                array('before_validation_on_create')
            )
        );
    }

    public function testRegister()
    {
        self::$_callback->register('before_create', 'register');
        $this->assertEquals(
            array('_init', 'register', 'before_save'),
            Reflection::invokeInstanceMethod(
                self::$_callback,
                '_getCallbacks',
                array('before_create')
            )
        );
    }

    public function testCall()
    {
        self::$_callback->register('before_create', function($model) {
            array_push($model::$result, 'closure');
        });

        $model = self::$_model;
        self::$_callback->call('before_create');
        $this->assertEquals(
            array('init', 'register', 'closure', 'save'),
            $model::$result
        );

        $model::$result = array();
        self::$_callback->call('before_create', true);
        $this->assertEquals(
            array('closure', 'save'),
            $model::$result
        );
    }
}