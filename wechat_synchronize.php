<?php
/**
 * @package wechat_synchronize
 * @version 1.0
 * \file wechat_synchronize.php
 */
/*
Plugin Name: wechat synchronize
Plugin URI: https://github.com/zhaofeng-shu33/wechat_synchronize_to_wordpress
Description: synchronize wechat articles to wordpress website
Author: zhaofeng-shu33
Version: 0.1
Author URI: https://github.com/zhaofeng-shu33
*/

// include third party depedency
require "wechat-php-sdk/autoload.php";
use Gaoming13\WechatPhpSdk;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\HttpCurl;

if (is_admin()) {
	add_action('admin_menu', 'ws_admin_menu');
    // this action is used to trigger synchronization of previous articles
    add_action('init', 'ws_process_request');
}
function ws_admin_menu(){
    add_options_page('ws options', 'ws', 'manage_options', 'ws-unique-identifier', 'ws_plugin_options');
    add_action('admin_init', 'register_ws_settings');
}
function register_ws_settings(){
    register_setting('ws-settings-group', 'appid');
    register_setting('ws-settings-group', 'appsecret');
    add_option('access_token');
}
function ws_plugin_options(){
    require_once 'setting-page.php';
}

/**
 * \brief Custom functions to retrieve the access_token from the database
 * @param: void
 * @return: valid access token string
 */
function ws_get_access_token(){
  // notice that the access_token is the serialized json string containing the expired time (UTC)
    return get_option('access_token');
}

/**
 * \brief Custom functions to save the access_token to the database
 * @param: access token string
 * @return: void
 */
function ws_save_access_token($token){
    update_option('access_token', $token);
}

/**
 * @param: valid access token
 * @return: url list of current wechat account prior to current date
 */
function ws_get_history_url(){
    $api = new Api(
	    array(
            'appId' => get_option('appid'),
            'appSecret'	=> get_option('appsecret'),
            'get_access_token' => 'ws_get_access_token',
            'save_access_token' => 'ws_save_access_token'
        )
    );
    list($err, $data) = $api->get_material_count();
    echo $data->news_count;
}

function ws_process_request(){
    // if no post data, return 
    $sync_history = isset($_REQUEST['ws_history']) ? true : false;
    if($sync_history){
        ws_get_history_url();
    }   
}
?>