<?php
namespace ScriptFUSION\Retry;

/**
 * The exception that is thrown when an operation fails too many times.
 */
class FailingTooHardException extends \RuntimeException
{
    public function __construct($attempts, \Exception $previous)
    {
        parent::__construct("Operation failed after $attempts attempt(s).", 0, $previous);
    }
}
