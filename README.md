Retry
=====

[![Latest version][Version image]][Releases]
[![Build status][Build image]][Build]
[![Test coverage][Coverage image]][Coverage]
[![Code style][Style image]][Style]

Retry provides a function to retry failing operations. An operation is deemed to have failed if it throws an exception.

Requirements
------------

- [PHP 5.5](http://php.net/)
- [Composer](https://getcomposer.org/)

Usage
-----

The `retry` function has the following signature.

```
retry(int $times, callable $operation);
```
* `$times` specifies how many times the operation may be called.
* `$operation` is a callback to be run up to the specified number of times.

Note that in the [original library](https://github.com/igorw/retry), `$times` specified the number of *retries* and therefore the operation could run up to `n + 1` times. In this version, `$times` specifies exactly the number of times the operation may run such that if zero (`0`) is specified it will never run.

### Example

```php
use function ScriptFUSION\Retry\retry;

// Try an operation up to 5 times.
$response = retry(5, function () use ($url) {
    return HttpConnector::fetch($url);
});
```


  [Releases]: https://github.com/ScriptFUSION/Retry/releases
  [Version image]: https://poser.pugx.org/scriptfusion/retry/v/stable "Latest version"
  [Build]: http://travis-ci.org/ScriptFUSION/Retry
  [Build image]: https://travis-ci.org/ScriptFUSION/Retry.svg "Build status"
  [Coverage]: https://coveralls.io/github/ScriptFUSION/Retry
  [Coverage image]: https://coveralls.io/repos/ScriptFUSION/Retry/badge.svg "Test coverage"
  [Style]: https://styleci.io/repos/62990558
  [Style image]: https://styleci.io/repos/62990558/shield?style=flat "Code style"

