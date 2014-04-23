<?php
/**
 * Bootstrap file of this plugin.
 *
 * @since 1.0
 */

/**
 * Plugin generator.
 *
 * @action plugins_loaded
 */
add_action('plugins_loaded', function(){
    // Disallow file edit.
    if( !defined('DISALLOW_FILE_EDIT') ){
        define('DISALLOW_FILE_EDIT', true);
    }
    // composer autoload.
    require dirname(__DIR__).'/vendor/autoload.php';
    // register self autoloader
    spl_autoload_register(function($class_name){
        $path = false;
        $class_name = ltrim($class_name, '\\');
        if( 0 === strpos($class_name, 'Giploy\\') ){
            $path = __DIR__.'/'.str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php';
            if( $path && file_exists($path) ){
                require $path;
            }
        }
    });
    // Get instance
    \Giploy\Admin::get_instance();
    \Giploy\Rewrite::get_instance();
});
