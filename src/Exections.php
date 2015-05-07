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
