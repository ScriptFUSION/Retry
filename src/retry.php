<?php
namespace ScriptFUSION\Retry;

/**
 * Tries the specified operation up to the specified number of times.
 *
 * @param int $tries Number of times.
 * @param callable $operation Operation.
 *
 * @return mixed Result of running the operation if tries is greater than zero,
 *     otherwise null.
 */
function retry($tries, callable $operation)
{
    $tries |= 0;

    if ($tries <= $attempts = 0) {
        return;
    }

    try {
        beginning:
        return $operation();
    } catch (\Exception $e) {
        if ($tries === ++$attempts) {
            throw new FailingTooHardException($attempts, $e);
        }

        goto beginning;
    }
}
