<?php
namespace ScriptFUSION\Retry;

/**
 * Tries the specified operation up to the specified number of times. If specified, the exception handler will be
 * called immediately before retrying the operation.
 *
 * @param int $tries Number of times.
 * @param callable $operation Operation.
 * @param callable $onError Optional. Exception handler.
 *
 * @return mixed Result of running the operation if tries is greater than zero, otherwise null.
 */
function retry($tries, callable $operation, callable $onError = null)
{
    if (($tries |= 0) <= $attempts = 0) {
        return;
    }

    try {
        beginning:
        return $operation();
    } catch (\Exception $exception) {
        if ($tries === ++$attempts) {
            throw new FailingTooHardException($attempts, $exception);
        }

        if ($onError && $onError($exception) === false) {
            return;
        }

        goto beginning;
    }
}
