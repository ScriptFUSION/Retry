<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Retry;

use PHPUnit\Framework\TestCase;
use ScriptFUSION\Retry\FailingTooHardException;
use function ScriptFUSION\Retry\retry;

final class RetryTest extends TestCase
{
    /**
     * Tests that when an operation is successful, its result is returned without retrying.
     */
    public function testWithoutFailing(): void
    {
        $invocations = 0;

        $value = retry($tries = 1, static function () use (&$invocations) {
            ++$invocations;

            return 5;
        });

        self::assertSame($tries, $invocations);
        self::assertSame(5, $value);
    }

    /**
     * Tests that when an operation fails once, it is retried.
     */
    public function testFailingOnce(): void
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

    /**
     * Tests that when an operation is attempted zero times, the operation is not invoked and returns null.
     */
    public function testZeroTries(): void
    {
        $invocations = 0;

        $value = retry($tries = 0, static function () use (&$invocations) {
            ++$invocations;

            return 5;
        });

        self::assertSame($tries, $invocations);
        self::assertNull($value);
    }

    /**
     * Tests that when an operation is retried the maximum number of tries, FailingTooHardException is thrown.
     */
    public function testFailingTooHard(): void
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
     * Tests that when an exception is thrown by the operation, the error callback receives that exception, the current
     * attempt index and maximum number of attempts.
     */
    public function testErrorCallback(): void
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
     * Tests that when an error handler returns false, retries are aborted.
     */
    public function testErrorCallbackHalt(): void
    {
        $invocations = 0;

        retry(2, static function () use (&$invocations) {
            ++$invocations;

            throw new \RuntimeException;
        }, fn () => false);

        self::assertSame(1, $invocations);
    }

    /**
     * Tests that when an exception handler throws an exception, the exception is not caught.
     */
    public function testErrorCallbackCanThrow(): void
    {
        $this->expectExceptionObject($exception = new \LogicException);

        retry(2, fn () => throw new \RuntimeException, fn () => throw $exception);
    }
}
