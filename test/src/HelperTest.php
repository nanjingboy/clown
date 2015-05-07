<?php
namespace Test\Src\ActiveRecord;

use Clown\Helper;
use PHPUnit_Framework_TestCase;

class HelperTest extends PHPUnit_Framework_TestCase
{
    public function testPluralize()
    {
        $this->assertEquals('Users', Helper::pluralize('User'));
    }

    public function testSingularize()
    {
        $this->assertEquals('User', Helper::singularize('Users'));
    }

    public function testCamelize()
    {
        $this->assertEquals('StringToCamelize', Helper::camelize('StringToCamelize'));
        $this->assertEquals('stringToCamelize', Helper::camelize('string_to_camelize', false));
        $this->assertEquals('StringToCamelize', Helper::camelize('string_to_camelize'));
        $this->assertEquals('stringToCamelize', Helper::camelize('string_to_camelize', false));
    }

    public function testUnderscore()
    {
        $this->assertEquals('string_to_underscore', Helper::underscore('string_to_underscore'));
        $this->assertEquals('string_to_underscore', Helper::underscore('StringToUnderscore'));
    }
}