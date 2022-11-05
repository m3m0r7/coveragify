<?php
declare(strict_types=1);

namespace Coveragify;

use function Composer\Autoload\includeFile;

class ClassLoader extends \Composer\Autoload\ClassLoader
{
    protected static array $coveragifySetting = [];
    protected static string $currentDirectory;

    public static function setConfigurations(string $currentDirectory, string $coveragifySettingFilePath = null)
    {
        static::$coveragifySetting = static::createSetting(json_decode(file_get_contents(
            $coveragifySettingFilePath ?? $currentDirectory . '/coveragify.json' ?? __DIR__ . '/../coveragify.json'
        ), true));

        static::$currentDirectory = $currentDirectory;
    }

    public function loadClass($class)
    {
        foreach (static::$coveragifySetting['ignoreDirectories'] as $ignoreDirectory) {
            $ignoreDirectory = realpath($ignoreDirectory);
            if (str_starts_with($ignoreDirectory, static::$currentDirectory)) {
                if ($file = $this->findFile($class)) {
                    var_dump($file);
                    var_dump("i:" . $class, $ignoreDirectory, static::$currentDirectory);
                    parent::loadClass($class);
                    return true;
                }
            }
        }

        foreach (static::$coveragifySetting['applyDirectories'] as $applyDirectory) {
            $applyDirectory = realpath($applyDirectory);
            if (str_starts_with($applyDirectory, static::$currentDirectory)) {
                if ($file = $this->findFile($class)) {
                    var_dump("c:" . $class, $applyDirectory, static::$currentDirectory);
                    Coveragifyable::includeFile($file);
                    return true;
                }
            }
        }

        var_dump("o:" . $class);
        parent::loadClass($class);

        return null;
    }

    protected static function createSetting(array $basedSetting = []): array
    {
        return [
            'extended' => null,
            'applyDirectories' => [],
            'ignoreDirectories' => [],
            ...$basedSetting
        ];
    }
}