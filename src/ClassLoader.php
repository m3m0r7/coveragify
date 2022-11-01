<?php
declare(strict_types=1);

namespace Coveragify;

use function Composer\Autoload\includeFile;

class ClassLoader extends \Composer\Autoload\ClassLoader
{
    protected static string $composerAutoloaderInit;
    protected static string $composerStaticInit;

    public static function setComposerConfigurations(string $composerAutoloaderInit, string $composerStaticInit)
    {
        static::$composerAutoloaderInit = $composerAutoloaderInit;
        static::$composerStaticInit = $composerStaticInit;
    }

    public function loadClass($class)
    {
        $directoryPaths = [
            ...array_keys((static::$composerStaticInit)::$classMap),
            ...array_keys((static::$composerStaticInit)::$prefixDirsPsr4),
        ];

        foreach ($directoryPaths as $path) {
            if (substr($class, 0, strlen($path)) === $path) {
                return parent::loadClass($class);
            }
        }

        if ($file = $this->findFile($class)) {
            Coveragifyable::includeFile($file);

            return true;
        }

        return null;
    }
}