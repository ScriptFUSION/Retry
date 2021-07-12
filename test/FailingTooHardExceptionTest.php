<?php
declare(strict_types=1);

namespace ScriptFUSIONTest\Retry;

use ScriptFUSION\Retry\FailingTooHardException;
use PHPUnit\Framework\TestCase;

/**
 * @see FailingTooHardException
 */
final class FailingTooHardExceptionTest extends TestCase
{
    public function testGetAttempts(): void
    {
        self::assertSame($attempts = 123, (new FailingTooHardException($attempts, new \Exception()))->getAttempts());
    }
}
