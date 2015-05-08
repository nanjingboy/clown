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