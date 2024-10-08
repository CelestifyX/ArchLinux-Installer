<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4e701191ef39eb5b8712daed3ad6c600
{
    public static $fallbackDirsPsr4 = array (
        0 => __DIR__ . '/../..' . '/src',
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->fallbackDirsPsr4 = ComposerStaticInit4e701191ef39eb5b8712daed3ad6c600::$fallbackDirsPsr4;
            $loader->classMap = ComposerStaticInit4e701191ef39eb5b8712daed3ad6c600::$classMap;

        }, null, ClassLoader::class);
    }
}
