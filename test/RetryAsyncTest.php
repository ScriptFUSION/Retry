<?php
namespace ScriptFUSIONTest\Retry;

use Amp\Delayed;
use ScriptFUSION\Retry\FailingTooHardException;

final class RetryAsyncTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests that a successful promise is returned without retrying.
     */
    public function testWithoutFailingAsync()
    {
        $invocations = 0;

        $value = \Amp\Promise\wait(
            \ScriptFUSION\Retry\retryAsync($tries = 1, static function () use (&$invocations) {
                ++$invocations;

                return new Delayed(0, 'foo');
            })
        );

        self::assertSame($tries, $invocations);
        self::assertSame('foo', $value);
    }

    /**
     * Tests that a failed promise is retried.
     */
    public function testFailingOnceAsync()
    {
        $invocations = 0;
        $failed = false;

        $value = \Amp\Promise\wait(
            \ScriptFUSION\Retry\retryAsync($tries = 2, static function () use (&$invocations, &$failed) {
                ++$invocations;

                if (!$failed) {
                    $failed = true;

                    throw new \RuntimeException;
                }

                return new Delayed(0, 'foo');
            })
        );

        self::assertTrue($failed);
        self::assertSame($tries, $invocations);
        self::assertSame('foo', $value);
    }

    /**
     * Tests that trying zero times yields null.
     */
    public function testZeroTriesAsync()
    {
        $invocations = 0;

        $value = \Amp\Promise\wait(
            \ScriptFUSION\Retry\retryAsync($tries = 0, static function () use (&$invocations) {
                ++$invocations;

                return new Delayed(0, 'foo');
            })
        );

        self::assertSame($tries, $invocations);
        self::assertNull($value);
    }

    /**
     * Tests that reaching maximum tries throws FailingTooHardException.
     */
    public function testFailingTooHardAsync()
    {
        $invocations = 0;
        $outerException = $innerException = null;

        try {
            \ScriptFUSION\Retry\retryAsync($tries = 3, static function () use (&$invocations, &$innerException) {
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
     * These that the error callback is called before each retry.
     */
    public function testErrorCallbackAsync()
    {
        $invocations = $errors = 0;
        $outerException = $innerException = null;

        try {
            \ScriptFUSION\Retry\retryAsync($tries = 3, static function () use (&$invocations, &$innerException) {
                ++$invocations;

                throw $innerException = new \RuntimeException;
            }, function (\Exception $exception) use (&$innerException, &$errors) {
                ++$errors;

                self::assertSame($innerException, $exception);
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
    public function testErrorCallbackHaltAsync()
    {
        $invocations = 0;

        \ScriptFUSION\Retry\retryAsync($tries = 2, static function () use (&$invocations) {
            ++$invocations;

            throw new \RuntimeException;
        }, static function () {
            return false;
        });

        self::assertSame(1, $invocations);
    }
}
