<?php
namespace Clown;

use Exception;

class ClownException extends Exception
{
}

class DatabaseException extends ClownException
{
}

class UndefinedColumnTypeException extends DatabaseException
{
}

class RelationshipBindException extends ClownException
{
    public function __construct($with, $target)
    {
        parent::__construct("Can't build relationship with {$with} for {$target}.");
    }

}

Class OperateDestroyedRecordException extends ClownException
{
    public function __construct()
    {
        parent::__construct("Can't operate the destroyed record.");
    }
}

class UndefinedMethodException extends ClownException
{
    public function __construct($method, $class)
    {
        parent::__construct("Call to undefined method {$class}::{$method}()");
    }
}

class UndefinedPropertyException extends ClownException
{
    public function __construct($property, $class)
    {
        parent::__construct("Undefined property {$class}::\${$property}");
    }
}

class PropertyReadOnlyException extends ClownException
{
    public function __construct($property, $class)
    {
        parent::__construct("Property {$class}::\${$property} is readonly.");
    }
}

class MissingArgumentException extends ClownException
{
    public function __construct($method, $class = null, $argumentNumber = 1)
    {
        parent::__construct("Missing at least {$argumentNumber} arguments for {$class}::{$method}()");
    }
}