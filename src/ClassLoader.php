<?php
declare(strict_types=1);

namespace Coveragify;

use function Composer\Autoload\includeFile;

class ClassLoader extends \Composer\Autoload\ClassLoader
{
    protected static string $currentDirectory;
    protected static array $ignoreDirectories = [];

    public static function setConfigurations(string $currentDirectory, array $ignoreDirectories)
    {
        static::$currentDirectory = $currentDirectory;
        static::$ignoreDirectories = $ignoreDirectories;
    }

    public function loadClass($class)
    {
        foreach (static::$ignoreDirectories as $ignoreDirectory) {
            $ignoreDirectory = realpath($ignoreDirectory);
            if (str_starts_with($ignoreDirectory, static::$currentDirectory)) {
                parent::loadClass($class);
                return true;
            }
        }

        if ($file = $this->findFile($class)) {
            Coveragifyable::includeFile($file);

            return true;
        }

        return null;
    }
}