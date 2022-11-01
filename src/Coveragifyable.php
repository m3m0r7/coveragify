<?php
declare(strict_types=1);

namespace Coveragify;

use Carbon\Carbon;

class Coveragifyable
{
    protected static string $temporaryDirectoryPath = __DIR__ . '/../.temp';

    public static function includeFile(string $path): string
    {
        $coveragifyableFilePath = static::$temporaryDirectoryPath . '/' . sha1($path) . '.php';
        @mkdir(static::$temporaryDirectoryPath);

        if (is_file($coveragifyableFilePath)) {
//            return $coveragifyableFilePath;
        }

        file_put_contents(
            $coveragifyableFilePath,
            static::transform(
                file_get_contents($path)
            ),
            LOCK_EX
        );

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

    public static function transform(string $code): string
    {
        $code = Coveragify::inspect($code)
            ->getCoveragifyableCode();
        if ($code === '') {
            return '';
        }
        return "<?php\n\n" . $code;
    }
}