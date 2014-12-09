<?php
namespace Models;
use \Clown\Model;

class Person extends Model
{
    public static $hasMany = array(
        array('books')
    );

    public static $hasOne = array(
        array(
            'friend',
            'class' => 'Person',
            'foreignKey' => 'person_id'
        )
    );
}