<?php
use \Models\Book;
use \Models\Person;
use \Clown\Db;
use \Clown\Schema;
use \Clown\Iterators\Model as ModelIterator;
use \Clown\Iterators\Record as RecordIterator;

class RelationshipTest extends PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        Schema::createTable('people', function($table) {
            $table->string('name');
            $table->integer('age');
            $table->integer('person_id');
            $table->boolean('disabled', array('default' => false));
        });

        Schema::createTable('books', function($table) {
            $table->string('name');
            $table->integer('person_id');
            $table->boolean('disabled', array('default' => false));
        });
    }

    public function setUp()
    {
        Db::instance()->execute('truncate table people');
        Db::instance()->execute('truncate table books');
    }

    public function testBelongs()
    {
        /**
         * Create
         */
        $book = new Book();
        $book->person = new Person();
        $book->person->name = 'Steven';
        $book->name = 'Fuck PHP';
        $book->save();
        $this->assertFalse($book->person->isNewRecord());
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'Steven',
                'age' => null,
                'person_id' => null,
                'disabled' => false
            ),
            $book->person->toArray()
        );

        /**
         * Update
         */
        $book = Book::find(1);
        $book->name = 'Fuck IE';
        $book->save();
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'Steven',
                'age' => null,
                'person_id' => null,
                'disabled' => false
            ),
            $book->person->toArray()
        );

        $book->person = new Person();
        $book->save();
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => null,
                'age' => null,
                'person_id' => null,
                'disabled' => false
            ),
            $book->person->toArray()
        );
    }

    public function testHasOne()
    {
        /**
         * Create
         */
        $person = new Person();
        $person->friend = new Person();
        $person->name = 'Tom';
        $person->save();
        $this->assertFalse($person->friend->isNewRecord());
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => null,
                'age' => null,
                'person_id' => 1,
                'disabled' => false
            ),
            $person->friend->toArray()
        );

        $person = new Person();
        $person->friend = Person::find(2);
        $person->save();
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => null,
                'age' => null,
                'person_id' => 3,
                'disabled' => false
            ),
            $person->friend->toArray()
        );

        /**
         * Update
         */
        $person = Person::find(2);
        $person->name = 'Tom';
        $person->friend = new Person();
        $person->save();
        $this->assertEquals(
            array(
                'id' => 4,
                'name' => null,
                'age' => null,
                'person_id' => 2,
                'disabled' => false
            ),
            $person->friend->toArray()
        );

        $person = Person::find(1);
        $person->name = 'Joy';
        $person->friend = Person::find(4);
        $person->save();
        $this->assertEquals(
            array(
                'id' => 4,
                'name' => null,
                'age' => null,
                'person_id' => 1,
                'disabled' => false
            ),
            $person->friend->toArray()
        );

        /**
         * Destroy
         */
        Person::find(1)->destroy();
        $this->assertEquals(
            array(
                'id' => 4,
                'name' => null,
                'age' => null,
                'person_id' => null,
                'disabled' => false
            ),
            Person::find(4)->toArray()
        );

        Person::$hasOne = array(
            array(
                'friend',
                'class' => 'Person',
                'foreignKey' => 'person_id',
                'dependent' => 'delete'
            )
        );
        Person::find(3)->destroy();
        $this->assertNull(Person::find(2));
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Tom',
                'age' => null,
                'person_id' => null,
                'disabled' => true
            ),
            Person::find(2, array('withDeleted' => true))->toArray()
        );


        Person::$hasOne = array(
            array(
                'friend',
                'class' => 'Person',
                'foreignKey' => 'person_id',
                'dependent' => 'destroy'
            )
        );
        $person = Person::find(2, array('withDeleted' => true));
        $person->restore();
        $person->friend = Person::find(4);
        $person->save();
        $person->destroy();
        $this->assertEquals(0, Person::count(array('withDeleted' => true)));
    }

    public function testHasMany()
    {
        /**
         * Create
         */
        $book = new Book();
        $book->name = 'C Pointer';
        $book->save();

        $person = new Person();
        $person->books->append($book);
        $person->books = new Book(array('name' => 'Elixir Language'));
        $person->save();
        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'name' => 'C Pointer',
                    'person_id' => 1,
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'name' => 'Elixir Language',
                    'person_id' => 1,
                    'disabled' => false
                )
            ),
            Book::find(
                array(
                    'conditions' => array('person_id' => 1),
                    'toArray' => true
                )
            )->getArrayCopy()
        );

        /**
         * Update
         */
        $person = Person::find(1);
        $person->books = new Book(array('name' => 'Javascript in action'));
        $person->save();
        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'name' => 'C Pointer',
                    'person_id' => 1,
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'name' => 'Elixir Language',
                    'person_id' => 1,
                    'disabled' => false
                ),
                array(
                    'id' => 3,
                    'name' => 'Javascript in action',
                    'person_id' => 1,
                    'disabled' => false
                )
            ),
            Book::find(
                array(
                    'conditions' => array('person_id' => 1),
                    'toArray' => true
                )
            )->getArrayCopy()
        );


        /**
         * Destroy
         */
        Person::find(1)->destroy();
        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'name' => 'C Pointer',
                    'person_id' => null,
                    'disabled' => false
                ),
                array(
                    'id' => 2,
                    'name' => 'Elixir Language',
                    'person_id' => null,
                    'disabled' => false
                ),
                array(
                    'id' => 3,
                    'name' => 'Javascript in action',
                    'person_id' => null,
                    'disabled' => false
                )
            ),
            Book::find(array('toArray' => true))->getArrayCopy()
        );

        Person::$hasMany = array(
            array(
                'books',
                'dependent' => 'delete'
            )
        );
        $books = Book::find();
        $person = new Person();
        foreach ($books as $book) {
            $person->books->append($book);
        }
        $person->save();
        $person->destroy();
        $this->assertEquals(
            array(
                array(
                    'id' => 1,
                    'name' => 'C Pointer',
                    'person_id' => null,
                    'disabled' => true
                ),
                array(
                    'id' => 2,
                    'name' => 'Elixir Language',
                    'person_id' => null,
                    'disabled' => true
                ),
                array(
                    'id' => 3,
                    'name' => 'Javascript in action',
                    'person_id' => null,
                    'disabled' => true
                )
            ),
            Book::find(
                array(
                    'toArray' => true,
                    'withDeleted' => true
                )
            )->getArrayCopy()
        );


        Person::$hasMany = array(
            array(
                'books',
                'dependent' => 'destroy'
            )
        );
        Book::restoreAll();
        $books = Book::find();
        $person = new Person();
        foreach ($books as $book) {
            $person->books->append($book);
        }
        $person->save();
        $person->destroy();
        $this->assertEquals(0, Book::count(array('withDeleted' => true)));
    }

    public function testQueryWithoutModelInstance()
    {
        $person = new Person();
        $person->name = 'Tom';
        $person->friend = new Person(array('name' => 'Meck'));
        $person->books = new Book(array('name' => 'Ruby'));
        $person->books = new Book(array('name' => 'Elixir'));
        $person->save();

        $person = Person::find(1, array('toArray' => true, 'withRelationships' => true));
        $this->assertTrue($person['friend'] instanceof RecordIterator);
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Meck',
                'age' => null,
                'person_id' => 1,
                'disabled' => false
            ),
            iterator_to_array($person['friend'], true)
        );

        $this->assertTrue($person['books'] instanceof ModelIterator);
        $this->assertEquals(2, $person['books']->count());
        $this->assertEquals(
            array(
                'id' => 1,
                'name' => 'Ruby',
                'person_id' => 1,
                'disabled' => false
            ),
            iterator_to_array($person['books'][0], true)
        );
        $this->assertEquals(
            array(
                'id' => 2,
                'name' => 'Elixir',
                'person_id' => 1,
                'disabled' => false
            ),
            iterator_to_array($person['books'][1], true)
        );
    }

    public static function tearDownAfterClass()
    {
        Schema::removeTable('people');
        Schema::removeTable('books');
    }
}