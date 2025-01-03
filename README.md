Retry
=====

[![Latest version][Version image]][Releases]
[![Total downloads][Downloads image]][Downloads]
[![Build status][Build image]][Build]
[![Test coverage][Coverage image]][Coverage]
[![Code style][Style image]][Style]

Retry provides a function to retry failing operations with optional [Fiber][Fibers] support.
An operation is deemed to have failed if it throws an exception.
This library is a rewrite of [Igor Wiedler's retry](https://github.com/igorw/retry) but aims to remain faithful to the
spirit of the original.

Requirements
------------

- [PHP 8.0](http://php.net/)
- [Composer](https://getcomposer.org/)

Usage
-----

The `retry` function retries an operation up to the specified number of times with an optional exception handler.

If an exception handler is specified, it is called immediately before retrying the operation. If the handler returns
`false`, the operation is not retried.

```
retry(int $times, callable $operation, callable $onError = null);
```
* `$times`&mdash;Maximum number of times the operation may run.
* `$operation`&mdash;Operation to run up to the specified number of times.
* `$onError`&mdash;Optional. Exception handler that receives the thrown exception as its first argument.

Note in the original library, `$times` specifies the number of *retries* and therefore the operation could run up to
`$times + 1` times. In this version, `$times` specifies exactly the number of times the operation may run such that if
zero (`0`) is specified it will not run.

### Example

The following code fragment attempts to fetch data from a URL over HTTP up to five times.

```php
use function ScriptFUSION\Retry\retry;

$response = retry(5, function () use ($url) {
    return HttpConnector::fetch($url);
});
```


  [Fibers]: https://www.php.net/manual/en/language.fibers.php

  [Releases]: https://github.com/ScriptFUSION/Retry/releases
  [Version image]: https://poser.pugx.org/scriptfusion/retry/v/stable "Latest version"
  [Downloads]: https://packagist.org/packages/scriptfusion/retry
  [Downloads image]: https://poser.pugx.org/scriptfusion/retry/downloads "Total downloads"
  [Build]: https://github.com/ScriptFUSION/Retry/actions/workflows/Tests.yaml
  [Build image]: https://github.com/ScriptFUSION/Retry/actions/workflows/Tests.yaml/badge.svg "Build status"
  [Coverage]: https://coveralls.io/github/ScriptFUSION/Retry
  [Coverage image]: https://coveralls.io/repos/ScriptFUSION/Retry/badge.svg "Test coverage"
  [Style]: https://styleci.io/repos/62990558
  [Style image]: https://styleci.io/repos/62990558/shield?style=flat "Code style"
