<?php
namespace Models;

use \Clown\Model;

class Validate extends Model
{
    public static $validatePresenceOf = array(
        array('presence')
    );

    public static $validateSizeOf = array(
        array('size_is', 'is' => 5),
        array('size_in', 'in' => array(5, 9)),
        array('size_minimum', 'minimum' => 9),
        array('size_maximum', 'maximum' => 15)
    );


    public static $validateInclusionOf = array(
        array('inclusion', 'in' => array('ruby', 'erlang'))
    );

    public static $validateExclusionOf = array(
        array('exclusion', 'in' => array('php', 'java'))
    );


    public static $validateFormatOf = array(
        array('format', 'with' => '/^[0-2][0-3]:[0-5][0-9]:[0-5][0-9]$/')
    );

    public static $validateNumericalityOf = array(
        array('onlyInteger', 'onlyInteger' => true),
        array('even', 'even' => true),
        array('odd', 'odd' => true),
        array('greater', 'greater' => 1),
        array('greaterOrEqual', 'greaterOrEqual' => 1),
        array('equal', 'equal' => 1),
        array('less', 'less' => 1),
        array('lessOrEqual', 'lessOrEqual' => 1),
    );

    public static $validateUniquenessOf = array(
        array('unique')
    );

    public static $validate = array(
        array('presenceShouldEqualsToTest')
    );


    public function presenceShouldEqualsToTest()
    {
        if ($this->presence === 'Test') {
            return true;
        }

        $this->addError('presence', 'should equals to test');
    }
}