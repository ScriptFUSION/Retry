<?php
declare(strict_types=1);

namespace ScriptFUSION\Retry;

use Amp\Coroutine;
use Amp\Promise;
use Amp\Success;

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
 */
function retry(int $tries, callable $operation, callable $onError = null)
{
    $generator = (static function () use ($tries, $operation, $onError): \Generator {
        // Nothing to do if tries less than or equal to zero.
        if ($tries <= $attempts = 0) {
            return;
        }

        try {
            beginning:

            if (($result = $operation()) instanceof Promise) {
                // Wait for promise to resolve.
                $result = yield $result;
            }
        } catch (\Exception $exception) {
            if ($tries === ++$attempts) {
                throw new FailingTooHardException($attempts, $exception);
            }

            if ($onError) {
                if (($result = $onError($exception, $attempts, $tries)) instanceof Promise) {
                    $result = yield $result;
                }

                if ($result === false) {
                    return;
                }
            }

            goto beginning;
        }

        if ($result instanceof \Generator) {
            throw new \UnexpectedValueException('Cannot retry a Generator. You probably meant something else.');
        }

        return $result;
    })();

    // Normal code path: generator runs without yielding.
    if (!$generator->valid()) {
        return $generator->getReturn();
    }

    // Async code path: generator yields promises.
    return $generator;
}

/**
 * Tries the specified operation up to the specified number of times. If specified, the exception handler will be
 * called immediately before retrying the operation. If the error handler returns false, the operation will not be
 * retried.
 *
 * @param int $tries Number of times.
 * @param callable $operation Operation.
 * @param callable|null $onError Optional. Exception handler.
 *
 * @return Promise Promise that returns the result of running the operation if tries is greater than zero, otherwise
 *     a promise that yields null.
 *
 */
function retryAsync(int $tries, callable $operation, callable $onError = null): Promise
{
    $generator = retry($tries, $operation, $onError);

    return $generator !== null ? new Coroutine($generator) : new Success(null);
}
