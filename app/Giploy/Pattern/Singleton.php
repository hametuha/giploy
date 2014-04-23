<?php

namespace Giploy\Pattern;

use Giploy\Git\Base;

/**
 * Common method container class
 *
 * @package Giploy
 * @since 1.0
 */
abstract class Singleton extends Base
{

    /**
     * Instance holder
     *
     * @var array
     */
    private static $instances = array();

    /**
     * Constructor
     */
    final private function __construct(){
        $this->initialized();
    }

    /**
     * Called when init hook if fired.
     */
    abstract public function initialized();

    /**
     * Instance getter
     *
     * @return mixed
     */
    public static function get_instance(){
        $class_name = get_called_class();
        if( !isset(self::$instances[$class_name]) ){
            self::$instances[$class_name] = new $class_name();
        }
        return self::$instances[$class_name];
    }
} 