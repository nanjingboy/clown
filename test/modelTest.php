<?php
use \Models\User;
use \Clown\Schema;
use \Clown\PropertyReadOnlyException;

class ModelTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Schema::createTable('users', function($table) {
            $table->integer('age');
            $table->string('name', array('length' => 40));
            $table->string('country', array('default' => 'China'));
            $table->boolean('disabled', array('default' => false));
        });
    }

    public function testParseConditions()
    {

        $this->assertEquals(
            array(
                'sql' => 'age > 10',
                'values' => array()
            ),
            User::parseConditions(array('age > 10'))
        );

        $this->assertEquals(
            array(
                'sql' => 'age > ?',
                'values' => array(10)
            ),
            User::parseConditions(array('age > ?', 10))
        );

        $this->assertEquals(
            array(
                'sql' => 'id in (?) or country = ?',
                'values' => array('1,2', 'China')
            ),
            User::parseConditions(
                array('id in (?) or country = ?', array(1, 2), 'China')
            )
        );

        $this->assertEquals(
            array(
                'sql' => 'id in (?) and country = ?',
                'values' => array('1,2', 'China')
            ),
            User::parseConditions(
                array('id' => array(1, 2), 'country' => 'China')
            )
        );
    }

    public function testCreate()
    {
        $this->assertEquals(1, User::Create(array('age' => 24, 'name' => 'Tom'))->id);
        $this->assertEquals(1, User::count());
        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 24,
                'name' => 'Tom',
                'country' => 'China',
                'disabled' => false
            ),
            User::first()->toArray()
        );

        $user = new User();
        $user->name = 'Joy';
        $user->age = 25;
        $user->save();
        $this->assertEquals(2, $user->id);
        $this->assertEquals(2, User::count());
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 25,
                'name' => 'Joy',
                'country' => 'China',
                'disabled' => false
            ),
            User::last()->toArray()
        );
    }

    public function testFind()
    {
        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 24,
                'name' => 'Tom',
                'country' => 'China',
                'disabled' => false,
                'alias_age' => '24'
            ),
            User::find(
                1, array('select' => array('*', 'age as alias_age'))
            )->toArray()
        );

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'age' => 25,
                    'name' => 'Joy',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::findByCountry(
                'China', array('toArray' => true)
            )->getArrayCopy()
        );

        $this->assertEquals(
            array(
                array(
                    'id' => null,
                    'age' => null,
                    'name' => 'Joy',
                    'country' => 'China',
                    'disabled' => false
                ),
                array(
                    'id' => null,
                    'age' => null,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::find(
                array(
                    'select' => array('name'),
                    'order' => 'id desc',
                    'toArray' => true
                )
            )->getArrayCopy()
        );

        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 24,
                'name' => 'Tom',
                'country' => 'China',
                'disabled' => false
            ),
            User::find(
                array(
                    'group' => 'id',
                    'having' => array('id' => 1),
                    'toArray' => true
                )
            )[0]
        );
    }

    public function testUpdate()
    {
        $user = User::find(2, array('select' => array('*', 'age as alias_age')));
        $user->age = 24;

        try {
            $user->aliasAge = 24;
        } catch(PropertyReadOnlyException $expected) {
        }

        try {
            $user->callback = null;
        } catch(PropertyReadOnlyException $expected) {
        }

        $this->assertEquals(25, $user->ageWas);
        $this->assertEquals(25, $user->age_was);
        $this->assertTrue($user->ageWasChanged());
        $user->age = 25;
        $this->assertFalse($user->ageWasChanged());

        $user->age = 24;
        $user->save();
        $this->assertEquals(
            array(
                'id' => 2,
                'age' => 24,
                'name' => 'Joy',
                'country' => 'China',
                'disabled' => false
            ),
            User::find(2)->toArray()
        );
        $this->assertEquals(24, $user->age);
        $this->assertEquals(24, $user->ageWas);
    }

    public function testDelete()
    {
        User::find(2)->delete();

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'age' => 24,
                    'name' => 'Joy',
                    'country' => 'China',
                    'disabled' => true
                )
            ),
            User::find(
                array('toArray' => true, 'withDeleted' => true)
            )->getArrayCopy()
        );

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::find(array('toArray' => true))->getArrayCopy()
        );
    }

    public function testRestore()
    {
        User::first(
            array('conditions' => array('id' => 2), 'withDeleted' => true)
        )->restore();

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'age' => 24,
                    'name' => 'Joy',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::find(
                array('toArray' => true, 'withDeleted' => true)
            )->getArrayCopy()
        );

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'age' => 24,
                    'name' => 'Joy',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::find(array('toArray' => true))->getArrayCopy()
        );
    }

    public function testDestroy()
    {
        User::find(2)->destroy();

        $this->assertEquals(
            array(
                'id' => 1,
                'age' => 24,
                'name' => 'Tom',
                'country' => 'China',
                'disabled' => false
            ),
            User::find(
                array('toArray' => true, 'withDeleted' => true)
            )[0]
        );

        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'age' => 24,
                    'name' => 'Tom',
                    'country' => 'China',
                    'disabled' => false
                )
            ),
            User::find(array('toArray' => true))->getArrayCopy()
        );
    }

    public static function tearDownAfterClass()
    {
        Schema::removeTable('users');
    }
}