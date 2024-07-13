<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit205e318d8cd760b7fa7a554a38f8c528
{
    public static $prefixLengthsPsr4 = array (
        'e' => 
        array (
            'enshrined\\svgSanitize\\' => 22,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'enshrined\\svgSanitize\\' => 
        array (
            0 => __DIR__ . '/..' . '/enshrined/svg-sanitize/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit205e318d8cd760b7fa7a554a38f8c528::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit205e318d8cd760b7fa7a554a38f8c528::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit205e318d8cd760b7fa7a554a38f8c528::$classMap;

        }, null, ClassLoader::class);
    }
}