<?php
namespace Clown;

use Exception;

class ClownException extends Exception
{
}

class ConnectionException extends ClownException
{
}

class CallbackException extends ClownException
{
}

class ColumnException extends ClownException
{
}

class MissingArgumentException extends ClownException
{
    public function __construct($method, $class, $argumentNumber = 1)
    {
        parent::__construct("Missing at least {$argumentNumber} arguments for {$class}::{$method}()");
    }
}

class MissingPrimaryKeyException extends ClownException
{
    public function __construct($class)
    {
        parent::__construct("Missing primary key for {$class}");
    }
}

class OperateDestroyedRecordException extends ClownException
{
    public function __construct()
    {
        parent::__construct("Can't operate the destroyed record.");
    }
}

class PropertyReadOnlyException extends ClownException
{
    public function __construct($property, $class)
    {
        parent::__construct("Property {$class}::\${$property} is readonly.");
    }
}

class UndefinedColumnTypeException extends ClownException
{
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