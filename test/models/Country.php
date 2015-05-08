<?php
use Clown\Model;

class Country extends Model
{
    public static $has_many = array(
        array(
            'users',
            'dependent' => 'destroy'
        )
    );
}