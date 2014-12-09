<?php
namespace Models;
use \Clown\Model;

class Book extends Model
{
    public static $belongsTo = array(
        array('person')
    );
}