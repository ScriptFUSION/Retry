<?php
declare(strict_types=1);

namespace ScriptFUSION\Retry;

/**
 * The exception that is thrown when an operation fails too many times.
 */
class FailingTooHardException extends \RuntimeException
{
    private $attempts;

    public function __construct(int $attempts, \Exception $previous)
    {
        parent::__construct("Operation failed after $attempts attempt(s).", 0, $previous);

        $this->attempts = $attempts;
    }

    /**
     * Gets the number of times the operation was attempted before giving up.
     *
     * @return int Number of attempts.
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
