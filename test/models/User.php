<?php
use Clown\Model;

class User extends Model
{
    public static $belongs_to = array(
        array('country')
    );

    public static $has_one = array(
        array(
            'friend',
            'class' => 'User',
            'foreign_key' => 'friend_id'
        )
    );
}