<?php
/*
Plugin Name: wechat synchronize
Plugin URI: https://github.com/zhaofeng-shu33/wechat_synchronize_to_wordpress
Description: synchronize wechat articles to wordpress website
Author: zhaofeng-shu33
Version: 0.3
Author URI: https://github.com/zhaofeng-shu33
*/
require_once "synchronize_api.php";
require_once 'insert_by_url.php';
if ( ! defined( 'ABSPATH' ) ) exit;

if (is_admin()) {
	add_action('admin_menu', 'wsync_admin_menu');
}
function wsync_admin_menu(){
    add_options_page('ws options', 'ws', 'manage_options', 'ws-unique-identifier', 'wsync_plugin_options');
    add_action('admin_init', 'wsync_register_settings');
}
function wsync_register_settings(){
    register_setting('ws-settings-group', 'appid');
    register_setting('ws-settings-group', 'appsecret');
    add_option('access_token');
}
function wsync_plugin_options(){
    require_once 'setting-page.php';
}



function wsync_process_request(){
    $sync_history = isset($_REQUEST['wsync_history']) ? $_REQUEST['wsync_history'] == 'wsync_Yes' : false;
    if($sync_history){
        if(isset($_REQUEST['offset'])){
            $return_array = array();
            $num = isset($_REQUEST['num']) ? intval($_REQUEST['offset']) : 20;
            wsync_get_history_url_by_offset($return_array, $_REQUEST['offset'], $num);            
        }
        else{
            $return_array = wsync_get_history_url();
        }
    }
    else{
        $urls_str = isset($_REQUEST['given_urls']) ? $_REQUEST['given_urls'] : '';
        if($urls_str != ''){
            $url_list = explode("\n", $urls_str);
            $return_array = wsync_insert_by_urls($url_list);
        }
        else{
            $return_array = array('post_id' => -9, 'err_msg' => 'no urls are given');
        }
        if(isset($_REQUEST['url_id'])){
            $return_array['url_id'] = $_REQUEST['url_id'];
        }
    }
    echo json_encode($return_array);
    wp_die();
}
add_action( 'wp_ajax_wsync_process_request', 'wsync_process_request' );

?>
