<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Retry;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Retry\FailingTooHardException;
use function ScriptFUSION\Retry\retry;

final class RetryTest extends TestCase
{
    public function testWithoutFailing()
    {
        $invocations = 0;

        $value = retry($tries = 1, static function () use (&$invocations) {
            ++$invocations;

            return 5;
        });

        self::assertSame($tries, $invocations);
        self::assertSame(5, $value);
    }

    public function testFailingOnce()
    {
        $invocations = 0;
        $failed = false;

        $value = retry($tries = 2, static function () use (&$invocations, &$failed) {
            ++$invocations;

            if (!$failed) {
                $failed = true;

                throw new \RuntimeException;
            }

            return 5;
        });

        self::assertTrue($failed);
        self::assertSame($tries, $invocations);
        self::assertSame(5, $value);
    }

    public function testZeroTries()
    {
        $invocations = 0;

        $value = retry($tries = 0, static function () use (&$invocations) {
            ++$invocations;

            return 5;
        });

        self::assertSame($tries, $invocations);
        self::assertNull($value);
    }

    public function testFailingTooHard()
    {
        $invocations = 0;
        $outerException = $innerException = null;

        try {
            retry($tries = 2, static function () use (&$invocations, &$innerException) {
                ++$invocations;

                throw $innerException = new \RuntimeException;
            });
        } catch (FailingTooHardException $outerException) {
        }

        self::assertInstanceOf(FailingTooHardException::class, $outerException);
        self::assertSame($innerException, $outerException->getPrevious());
        self::assertSame($tries, $invocations);
    }

    /**
     * Tests that an error callback receives the exception thrown by the operation, the current attempt and maximum
     * number of attempts.
     */
    public function testErrorCallback()
    {
        $invocations = $errors = 0;
        $outerException = $innerException = null;

        try {
            retry($tries = 2, static function () use (&$invocations, &$innerException) {
                ++$invocations;

                throw $innerException = new \RuntimeException;
            }, static function (\Exception $exception, int $attempts, int $tries) use (&$innerException, &$errors) {
                ++$errors;

                self::assertSame($innerException, $exception);
                self::assertSame(1, $attempts);
                self::assertSame(2, $tries);
            });
        } catch (FailingTooHardException $outerException) {
        }

        self::assertInstanceOf(FailingTooHardException::class, $outerException);
        self::assertSame($tries, $invocations);
        self::assertSame($tries - 1, $errors);
    }

    /**
     * Tests that an error handler that returns false aborts retrying.
     */
    public function testErrorCallbackHalt()
    {
        $invocations = 0;

        retry($tries = 2, static function () use (&$invocations) {
            ++$invocations;

            throw new \RuntimeException;
        }, static function () {
            return false;
        });

        self::assertSame(1, $invocations);
    }
}
