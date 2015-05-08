<?php
namespace Test\Src;

use User;
use Clown\Model;
use Clown\Schema;
use Clown\PropertyReadOnlyException;
use Clown\OperateDestroyedRecordException;

use PHPUnit_Framework_TestCase;

class ModelTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Schema::createTable('users', function($table) {
            $table->integer('id', array('auto_increment' => true));
            $table->integer('age');
            $table->string('name', array('length' => 40));
            $table->integer('country_id');
            $table->integer('friend_id');
            $table->index(array('name'));
        });
    }

    public function testTable()
    {
        $this->assertEquals('users', User::table());
    }

    public function testCreate()
    {
        $this->assertEquals(
            1,
            User::Create(
                array(
                    'age' => 24,
                    'name' => 'Tom'
                )
            )->id
        );

        $this->assertEquals(1, User::count());
        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 24,
                'name' => 'Tom',
                'country_id' => null,
                'friend_id' => null
            ),
            User::first()->toArray()
        );

        $user = new User();
        $user->name = 'Joy';
        $user->age = 25;
        $this->assertTrue($user->isNewRecord());
        $this->assertTrue($user->save());
        $this->assertFalse($user->isNewRecord());
        $this->assertEquals(2, $user->id);
        $this->assertEquals(2, User::count());
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 25,
                'name' => 'Joy',
                'country_id' => null,
                'friend_id' => null
            ),
            $user->toArray()
        );
        $this->assertEquals($user->toArray(), User::last()->toArray());
    }

    public function testUpdate()
    {
        $user = User::first(
            array(
                'conditions' => array('id' => 2),
                'select' => array('*', 'age as alias_age')
            )
        );

        $user->age = 24;
        try {
            $user->alias_age = 24;
        } catch(PropertyReadOnlyException $expected) {
        }

        $this->assertEquals(25, $user->age_was);
        $this->assertTrue($user->age_was_changed());
        $user->age = 25;
        $this->assertFalse($user->age_was_changed());
        $user->age = 24;
        $this->assertTrue($user->save());
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 24,
                'name' => 'Joy',
                'country_id' => null,
                'friend_id' => null
            ),
            User::last()->toArray()
        );
        $this->assertEquals(24, $user->age);
        $this->assertEquals(24, $user->age_was);
    }

    public function testDestroy()
    {
        $this->assertEquals(2, User::count());

        $user = User::last();
        $this->assertTrue($user->destroy());

        try {
            $user->save();
        } catch(OperateDestroyedRecordException $expected) {
        }

        $this->assertEquals(1, User::count());
    }

    public static function tearDownAfterClass()
    {
        Schema::removeTable('users');
    }
}