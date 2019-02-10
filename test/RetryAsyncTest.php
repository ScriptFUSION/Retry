<?php
namespace ScriptFUSIONTest\Retry;

use Amp\Delayed;
use Amp\Promise;
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
     * Tests that the error callback is called before each retry.
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
     * Tests that an async error callback is processed asynchronously.
     */
    public function testAsyncErrorCallback()
    {
        $delay = 250; // Quarter of a second.
        $start = microtime(true);

        try {
            \Amp\Promise\wait(
                \ScriptFUSION\Retry\retryAsync($tries = 3, static function () {
                    throw new \DomainException;
                }, static function () use ($delay): Promise {
                    return new Delayed($delay);
                })
            );
        } catch (FailingTooHardException $outerException) {
            self::assertInstanceOf(\DomainException::class, $outerException->getPrevious());
        }

        self::assertTrue(isset($outerException));
        self::assertGreaterThan($start + $delay * ($tries - 1) / 1000, microtime(true));
    }

    /**
     * Tests that an error handler that returns false aborts retrying.
     */
    public function testErrorCallbackHaltAsync()
    {
        $invocations = 0;

        \ScriptFUSION\Retry\retryAsync(2, static function () use (&$invocations) {
            ++$invocations;

            throw new \RuntimeException;
        }, static function () {
            return false;
        });

        self::assertSame(1, $invocations);
    }

    /**
     * Tests that the exception handler can throw an exception that will not be caught.
     */
    public function testErrorCallbackCanThrow()
    {
        $this->setExpectedException(\LogicException::class);

        \ScriptFUSION\Retry\retryAsync(2, static function () {
            throw new \RuntimeException;
        }, static function () {
            throw new \LogicException;
        });
    }
}
