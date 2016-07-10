<?php
namespace ScriptFUSIONTest\Retry;

use ScriptFUSION\Retry\FailingTooHardException;

final class RetryTest extends \PHPUnit_Framework_TestCase
{
    public function testWithoutFailing()
    {
        $invocations = 0;

        $value = \ScriptFUSION\Retry\retry($tries = 1, function () use (&$invocations) {
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

        $value = \ScriptFUSION\Retry\retry($tries = 2, function () use (&$invocations, &$failed) {
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

        $value = \ScriptFUSION\Retry\retry($tries = 0, function () use (&$invocations) {
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
            \ScriptFUSION\Retry\retry($tries = 2, function () use (&$invocations, &$innerException) {
                ++$invocations;

                throw $innerException = new \RuntimeException;
            });
        } catch (FailingTooHardException $outerException) {
        }

        self::assertInstanceOf(FailingTooHardException::class, $outerException);
        self::assertSame($innerException, $outerException->getPrevious());
        self::assertSame($tries, $invocations);
    }

    public function testErrorCallback()
    {
        $invocations = $errors = 0;
        $outerException = $innerException = null;

        try {
            \ScriptFUSION\Retry\retry($tries = 2, function () use (&$invocations, &$innerException) {
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
        self::assertSame(1, $errors);
    }
}
