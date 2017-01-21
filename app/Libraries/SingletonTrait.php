<?php

/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

// From http://eddmann.com/posts/accessors-getter-setter-and-singleton-traits-in-php/
trait SingletonTrait
{
    protected static $instance;

    final public static function getInstance()
    {
        if (!isset(self::$instance)) {
            $class = new ReflectionClass(__CLASS__);
            self::$instance = $class->newInstanceArgs(func_get_args());
        }

        return self::$instance;
    }

    final private function __clone()
    {
    }

    final private function __wakeup()
    {
    }
}
