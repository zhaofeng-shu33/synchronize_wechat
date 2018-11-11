<?php
/**
 * @package wechat_synchronize
 * @version 1.0
 */
/*
Plugin Name: wechat synchronize
Plugin URI: https://github.com/zhaofeng-shu33/wechat_synchronize_to_wordpress
Description: synchronize wechat articles to wordpress website
Author: zhaofeng-shu33
Version: 0.1
Author URI: https://github.com/zhaofeng-shu33
*/
if (is_admin()) {
	add_action('admin_menu', 'ws_admin_menu');
}
function ws_admin_menu(){
    add_options_page('ws options', 'ws', 'manage_options', 'ws-unique-identifier', 'ws_plugin_options');
    add_action('admin_init', 'register_ws_settings');
}
function register_ws_settings(){
    register_setting('ws-settings-group', 'appid');
    register_setting('ws-settings-group', 'appsecret');
}
function ws_plugin_options(){
    require_once 'setting-page.php';
}
?>