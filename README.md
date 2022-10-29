# What is this?

The coveragify is getting coverage for your PHP codes on PHP which no requires to install an extension.

*This is experimental implementation for me.*

# The problem of PHP for getting coverage

The PHP users need to install some extension XDebug or pcov when getting coverages on PHP currently.
In the way, they extensions are not working on concurrently or parallels; for examples, Swoole, parallel, pthreads and so on.
And so, it requires to build on your environment - it is so highly cost, but we wants really to install easily getting coverage.
This library is solving they problems because this library is written in PHP 100% - yes so, no build and no install.

# How to use?

Install this library via below command:

```sh
composer require m3m0r7/coveragify
```

And run a command which is patching for a part of PHPUnit files:

```sh
./vendor/bin/coveragify patch
```

If you need to rollback to previously states then running below command.

```sh
./vendor/bin/coveragify unpatch
```
