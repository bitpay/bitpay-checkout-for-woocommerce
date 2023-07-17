<?php

namespace BitPayKeyUtils\Math;

use Exception;

class Math
{
    private static $engine;
    private static $engineName;

    public static function getEngine()
    {
        return static::$engine;
    }

    public static function setEngine($engine)
    {
        static::$engine = $engine;
    }

    public static function getEngineName()
    {
        return static::$engineName;
    }

    public static function setEngineName($engineName)
    {
        static::$engineName = $engineName;
    }

    public static function __callStatic($name, $arguments)
    {
        if (is_null(static::$engine)) {
            if (extension_loaded('gmp')) {
                static::$engine = new GmpEngine();
                static::$engineName = "GMP";
            // @codeCoverageIgnoreStart
            } elseif (extension_loaded('bcmath')) {
                static::$engine = new BcEngine();
                static::$engineName = "BCMATH";
            } else {
                throw new Exception('The GMP or BCMATH extension for PHP is required.');
            }
            // @codeCoverageIgnoreEnd
        }

        return call_user_func_array(array(static::$engine, $name), $arguments);
    }
}
