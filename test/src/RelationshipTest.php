<?php
namespace Test\Src;

use User;
use Country;
use Clown\Schema;
use Clown\Connection;
use PHPUnit_Framework_TestCase;

class RelationshipTest extends PHPUnit_Framework_TestCase
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

        Schema::createTable('countries', function($table) {
            $table->integer('id', array('auto_increment' => true));
            $table->string('name');
        });
    }

    public function setUp()
    {
        Connection::instance()->execute('truncate table users');
        Connection::instance()->execute('truncate table countries');

        User::$has_one = array(
            array(
                'friend',
                'class' => 'User',
                'foreign_key' => 'friend_id'
            )
        );
        Country::$has_many = array(
            array('users')
        );
    }

    public function testBelongsTo()
    {
        /**
         * Create
         */
        $user = new User();
        $user->country = new Country();
        $user->country->name = 'China';
        $user->save();
        $this->assertEquals(1, $user->country_id);
        $this->assertFalse($user->country->isNewRecord());
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'China'
            ),
            $user->country->toArray()
        );

        /**
         * Update
         */
        $user = User::first(array('conditions' => array('id' => 1)));
        $user->name = 'Tom';
        $user->save();
        $this->assertEquals(1, $user->country_id);
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'China'
            ),
            $user->country->toArray()
        );
        $user->country = new Country(array('name' => 'Japan'));
        $user->save();
        $this->assertEquals(2, $user->country_id);
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Japan'
            ),
            $user->country->toArray()
        );
    }

    public function testHasOne()
    {
        /**
         * Create
         */
        $user = new User();
        $user->name = 'Tom';
        $user->age = 25;
        $user->init_friend(array('name' => 'Joy', 'age' => 26));
        $user->save();
        $this->assertFalse($user->friend->isNewRecord());
        $this->assertEquals(
            $user->friend->toArray(),
            User::first(array('conditions' => array('id' => 2)))->toArray()
        );
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 26,
                'name' => 'Joy',
                'country_id' => null,
                'friend_id' => 1
            ),
            $user->friend->toArray()
        );

        $user = new User(array('name' => 'Mike', 'age' => 27));
        $user->friend = User::first(array('conditions' => array('id' => 2)));
        $user->save();
        $this->assertEquals(
            $user->friend->toArray(),
            User::first(array('conditions' => array('id' => 2)))->toArray()
        );
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 26,
                'name' => 'Joy',
                'country_id' => null,
                'friend_id' => 3
            ),
            $user->friend->toArray()
        );

        /**
         * Update
         */
        $user = User::first(array('conditions' => array('id' => 3)));
        $user->friend = User::first(array('conditions' => array('id' => 1)));
        $user->save();
        $this->assertEquals(
            $user->friend->toArray(),
            User::first(array('conditions' => array('id' => 1)))->toArray()
        );
        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 25,
                'name' => 'Tom',
                'country_id' => null,
                'friend_id' => 3
            ),
            $user->friend->toArray()
        );
        $this->assertEquals(1, User::count(array('conditions' => array('friend_id' => 3))));
        $this->assertNull(User::first(array('conditions' => array('id' => 2)))->friend_id);

        /**
         * Destroy
         */
        User::first(array('conditions' => array('id' => 3)))->destroy();
        $this->assertEquals(0, User::count(array('conditions' => array('friend_id' => 3))));
        $this->assertEquals(2, User::count());
        $this->assertNull(User::first(array('conditions' => array('id' => 1)))->friend_id);

        User::$has_one = array(
            array(
                'friend',
                'class' => 'User',
                'foreign_key' => 'friend_id',
                'dependent' => 'destroy'
            )
        );
        $user = new User(array('name' => 'Mike', 'age' => 27));
        $user->friend = User::first(array('conditions' => array('id' => 2)));
        $user->save();
        User::first(array('conditions' => array('id' => 4)))->destroy();
        $this->assertEquals(1, User::count());
    }

    public function testHasMany()
    {
        /**
         * Create
         */
        $user = new User();
        $user->name = 'Joy';
        $user->age = 26;
        $user->save();

        $country = new Country();
        $country->name = 'China';
        $country->users->append($user);
        $country->init_user(array('name' => 'Tom', 'age' => 25));
        $country->save();

        $users = User::find(array('conditions' => array('country_id' => 1)));
        $this->assertEquals(2, count($users));
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'Joy',
                'age' => 26,
                'country_id' => 1,
                'friend_id' => null
            ),
            $users[0]->toArray()
        );
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Tom',
                'age' => 25,
                'country_id' => 1,
                'friend_id' => null
            ),
            $users[1]->toArray()
        );

        /**
         * Update
         */
        $country = Country::first(array('conditions' => array('id' => 1)));
        $country->users = new User(array('name' => 'Mike', 'age' => 27));
        $country->save();

        $users = User::find(array('conditions' => array('country_id' => 1)));
        $this->assertEquals(3, count($users));
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'Joy',
                'age' => 26,
                'country_id' => 1,
                'friend_id' => null
            ),
            $users[0]->toArray()
        );
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Tom',
                'age' => 25,
                'country_id' => 1,
                'friend_id' => null
            ),
            $users[1]->toArray()
        );

        /**
         * Destroy
         */
        Country::first(array('conditions' => array('id' => 1)))->destroy();
        $this->assertEquals(0, User::count(array('conditions' => array('country_id' => 1))));

        $users = User::find();
        $this->assertEquals(3, count($users));
        $this->assertNull($users[0]->country_id);
        $this->assertNull($users[1]->country_id);
        $this->assertNull($users[2]->country_id);

        Country::$has_many = array(
            array(
                'users',
                'class' => 'User',
                'dependent' => 'destroy'
            )
        );
        $country = new Country();
        $country->name = 'China';
        $country->users = $users;
        $country->save();
        $this->assertEquals(3, User::count(array('conditions' => array('country_id' => 2))));
        $country->destroy();
        $this->assertEquals(0, User::count(array('conditions' => array('country_id' => 2))));
        $this->assertEquals(0, User::count());
    }

    public static function tearDownAfterClass()
    {
        Schema::removeTable('users');
        Schema::removeTable('countries');
    }
}