<?php
declare(strict_types=1);

namespace ScriptFUSION\Retry;

/**
 * Tries the specified operation up to the specified number of times. If specified, the exception handler will be
 * called immediately before retrying the operation. If the error handler returns false, the operation will not be
 * retried.
 *
 * @param int $tries Number of times.
 * @param callable $operation Operation.
 * @param callable|null $onError Optional. Exception handler.
 *
 * @return mixed Result of running the operation if tries is greater than zero, otherwise null.
 *
 * @throws FailingTooHardException The maximum number of attempts was reached.
 * @throws \UnexpectedValueException The operation returned an unsupported type.
 */
function retry(int $tries, callable $operation, callable $onError = null): mixed
{
    // Nothing to do if tries less than or equal to zero.
    if ($tries <= $attempts = 0) {
        return null;
    }

    try {
        beginning:
        $result = $operation();
    } catch (\Exception $exception) {
        if ($tries === ++$attempts) {
            throw new FailingTooHardException($attempts, $exception);
        }

        if ($onError && $onError($exception, $attempts, $tries) === false) {
            return null;
        }

        goto beginning;
    }

    if ($result instanceof \Generator) {
        throw new \UnexpectedValueException('Cannot retry a Generator. You probably meant something else.');
    }

    return $result;
}
