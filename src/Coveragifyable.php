<?php
declare(strict_types=1);

namespace Coveragify;

use Carbon\Carbon;

class Coveragifyable extends \php_user_filter
{
    protected static string $temporaryDirectoryPath = __DIR__ . '/../.temp';

    public static function includeFile(string $path): string
    {
        $coveragifyableFilePath = static::$temporaryDirectoryPath . '/' . sha1($path) . '.php';
        @mkdir(static::$temporaryDirectoryPath);

        if (is_file($coveragifyableFilePath)) {
            return $coveragifyableFilePath;
        }

        $handle = fopen($path, 'r');
        try {
            stream_filter_append($handle, 'coveragifyable');

            file_put_contents(
                $coveragifyableFilePath,
                stream_get_contents($handle),
                LOCK_EX
            );
        } finally {
            fclose($handle);
        }

        return $coveragifyableFilePath;
    }

    public static function setTemporaryDirectory(string $temporaryPath): void
    {
        static::$temporaryDirectoryPath = $temporaryPath;
    }

    public static function getTemporaryDirectory(): string
    {
        return static::$temporaryDirectoryPath;
    }

    public function filter($in, $out, &$consumed, bool $closing): int
    {
        $baesdBucket = stream_bucket_new(
            fopen('php://memory', 'r'),
            ''
        );

        while ($bucket = stream_bucket_make_writeable($in)) {
            $baesdBucket->data .= $bucket->data;
        }

        $baesdBucket->data = $this->transform($baesdBucket->data);
        $consumed = $baesdBucket->datalen = strlen($baesdBucket->data);

        stream_bucket_append($out, $baesdBucket);
        return \PSFS_PASS_ON;
    }

    public function transform(string $code): string
    {
        $code = Coveragify::inspect($code)
            ->getCoveragifyableCode();
        if ($code === '') {
            return '';
        }
        return "<?php\n\n" . $code;
    }
}