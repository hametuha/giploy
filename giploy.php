<?php
/*
Plugin Name: giploy
Plugin URI: http://wordpress.org/extend/plugins/giploy/
Description: This plugin allows you to deploy Github hotsted theme or plugin with service hook.
Author: Takahashi Fumiki
Version: 1.0
Author URI: http://takahashifumiki.com
Text Domain: giploy
*/

// Do not load directly
defined('ABSPATH') or die();


// Add text domain
load_plugin_textdomain('giploy', false, 'giploy/language');


if( version_compare(PHP_VERSION, '5.3.0') >= 0 && (version_compare(PHP_VERSION, '5.3.0') >= 0 || ini_get('short_open_tag')) ){
    // O.K. Load bootsrap.
    require __DIR__.'/app/bootstrap.php';
}else{

    /**
     * Show error message on admin
     *
     * @ignore
     */
    function _giploy_error(){
        printf('<div class="error"><p>%s</p></div>', sprintf(__('<strong>[giploy Error]</strong> Your PHP version is %s, but giploy requires 5.3.0 and over. And on 5.3.*, you must set <code>short_open_tag = on</code> because <code>&lt;?=</code> tags are used here and there. For more info, see <a target="_blank" href="%s">PHP Manual</a>', 'giploy'), PHP_VERSION, 'http://www.php.net/manual/en/ini.core.php'));
    }
    // If PHP is < 5.3, show message and finish.
    add_action('admin_notices', '_giploy_error');
}
