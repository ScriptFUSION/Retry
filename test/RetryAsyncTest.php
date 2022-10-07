<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Retry;

use Amp\Future;
use PHPUnit\Framework\TestCase;
use ScriptFUSION\Retry\FailingTooHardException;
use function Amp\async;
use function Amp\delay;
use function ScriptFUSION\Retry\retry;

final class RetryAsyncTest extends TestCase
{
    /**
     * Tests that a successful Future is returned without retrying.
     */
    public function testWithoutFailingAsync(): void
    {
        $invocations = 0;

        $value =
            retry($tries = 1, static function () use (&$invocations) {
                ++$invocations;

                return self::delayAndReturn(0, 'foo');
            })
        ;

        self::assertSame($tries, $invocations);
        self::assertSame('foo', $value);
    }

    /**
     * Tests that a failed Future is retried.
     */
    public function testFailingOnceAsync(): void
    {
        $invocations = 0;
        $failed = false;

        $value =
            retry($tries = 2, static function () use (&$invocations, &$failed) {
                ++$invocations;

                if (!$failed) {
                    $failed = true;

                    throw new \RuntimeException;
                }

                return self::delayAndReturn(0, 'foo');
            })
        ;

        self::assertTrue($failed);
        self::assertSame($tries, $invocations);
        self::assertSame('foo', $value);
    }

    /**
     * Tests that trying zero times yields null.
     */
    public function testZeroTriesAsync(): void
    {
        $invocations = 0;

        $value =
            retry($tries = 0, static function () use (&$invocations) {
                ++$invocations;

                return self::delayAndReturn(0, 'foo');
            })
        ;

        self::assertSame($tries, $invocations);
        self::assertNull($value);
    }

    /**
     * Tests that reaching maximum tries throws FailingTooHardException.
     */
    public function testFailingTooHardAsync(): void
    {
        $invocations = 0;
        $outerException = $innerException = null;

        try {
            retry($tries = 3, static function () use (&$invocations, &$innerException) {
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
    public function testErrorCallbackAsync(): void
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
     * Tests that an error callback that returns a Future has its Future resolved.
     */
    public function testFutureErrorCallback(): void
    {
        $delay = .25; // Quarter of a second.
        $start = microtime(true);

        try {
            retry($tries = 3, static function () {
                throw new \DomainException;
            }, fn () => self::delayAndReturn($delay));
        } catch (FailingTooHardException $outerException) {
            self::assertInstanceOf(\DomainException::class, $outerException->getPrevious());
        }

        self::assertTrue(isset($outerException));
        self::assertGreaterThan($start + $delay * ($tries - 1), microtime(true));
    }

    /**
     * Tests that when error handler that returns false, it aborts retrying.
     */
    public function testErrorCallbackHaltAsync(): void
    {
        $invocations = 0;

        retry(2, static function () use (&$invocations): never {
            ++$invocations;

            throw new \RuntimeException;
        }, fn () => false);

        self::assertSame(1, $invocations);
    }

    /**
     * Tests that when an error handler returns a Future that false, it aborts retrying.
     */
    public function testFutureErrorCallbackHaltAsync(): void
    {
        $invocations = 0;

        retry(2, static function () use (&$invocations): never {
            ++$invocations;

            throw new \RuntimeException;
        }, fn () => Future::complete(false));

        self::assertSame(1, $invocations);
    }

    /**
     * Tests that the exception handler can throw an exception that will not be caught.
     */
    public function testErrorCallbackCanThrow(): void
    {
        $this->expectException(\LogicException::class);

        retry(2, static function (): never {
            throw new \RuntimeException;
        }, static function (): never {
            throw new \LogicException;
        });
    }

    private static function delayAndReturn(float $delay, string $return = null): Future
    {
        return async(static function () use ($delay, $return): ?string {
            delay($delay);

            return $return;
        });
    }
}
