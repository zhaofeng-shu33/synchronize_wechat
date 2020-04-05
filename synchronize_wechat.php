<?php
/*
Plugin Name: synchronize wechat
Plugin URI: https://github.com/zhaofeng-shu33/synchronize_wechat
Description: synchronize wechat articles to wordpress website
Author: zhaofeng-shu33
Version: 0.9
Author URI: https://github.com/zhaofeng-shu33
Text Domain: synchronize-wechat
Domain Path: /languages/
*/
/**
 * @file synchronize_wechat.php
 */
require_once "synchronize_api.php";
require_once 'insert_by_url.php';
if ( ! defined( 'ABSPATH' ) ) exit;

if (is_admin()) {
	add_action( 'init', 'sw_load_plugin_textdomain' );
	add_action('admin_menu', 'sync_wechat_admin_menu');
}

function sw_load_plugin_textdomain(){
	$text_domain = 'synchronize-wechat';
	$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	load_plugin_textdomain( $text_domain, false, $lang_dir );
}

//! \brief initialize admin menu as submenu under **Settings**
function sync_wechat_admin_menu(){
//     add_options_page('sync_wechat options', 'sync_wechat', 'manage_options', 'sync_wechat-unique-identifier', 'sync_wechat_plugin_options');
	add_submenu_page('edit.php', __('sync_wechat', 'synchronize-wechat'), __('sync_wechat', 'synchronize-wechat'), 'edit_posts', 'sync_wechat-unique-identifier', 'sync_wechat_plugin_options');
    add_action('admin_init', 'sync_wechat_register_settings');
}

//! \brief register setting data for persistent storage and create cache directory
function sync_wechat_register_settings(){
    if(!file_exists(__DIR__ . '/asset')){
        mkdir(__DIR__ . '/asset');
    }
    register_setting('sync_wechat-settings-group', 'appid');
    register_setting('sync_wechat-settings-group', 'appsecret');
    add_option('access_token');
}

//! \brief  load the frontend page
function sync_wechat_plugin_options(){
    require_once 'setting-page.php';
}

//! \brief  basic config setting function
function sync_wechat_set_config(){

    $changePostTime = isset($_POST['change_post_time']) && sanitize_text_field($_POST['change_post_time']) == 'true';
    $postStatus     = isset($_POST['post_status']) && in_array(sanitize_text_field($_POST['post_status']), array('publish', 'pending', 'draft')) ?
                                            $_POST['post_status'] : 'publish';
    $keepStyle      = isset($_POST['keep_style']) && sanitize_text_field($_POST['keep_style']) == 'keep';
    $keepSource      = isset($_POST['keep_source']) ? sanitize_text_field($_POST['keep_source']) == 'keep': true;
	$debug          = isset($_POST['debug']) ? sanitize_text_field($_POST['debug']) == 'on' : true;
    $config = array(
		'changePostTime'  => $changePostTime,
		'postStatus'   => $postStatus,
		'keepStyle'     => $keepStyle,
        'keepSource' => $keepSource,
        'setFeatureImage' => true,
        'debug' => $debug
    );
    return $config;
}

//! \brief ajax callback main function
function sync_wechat_process_request(){
    $sync_history = isset($_POST['sync_wechat_history']) ? sanitize_text_field($_POST['sync_wechat_history']) == 'sync_wechat_Yes' : false;
    $date_check = isset($_POST['sync_wechat_date_check']) ? sanitize_text_field($_POST['sync_wechat_date_check']) == 'Yes' : true;
    if($sync_history){
        if(isset($_POST['offset'])){
            $num = isset($_POST['num']) ? intval($_POST['num']) : 20;
            if($num <=0 || $num >20){
                $return_array = array('status_code' => -10, 'err_msg' => 'invalid num given');
            }
            $offset = intval($_POST['offset']);
            $return_array = sync_wechat_get_history_url_by_offset($offset, $num, null, $date_check);
        }
        else{ //if no offset parameter, raise the error
            $return_array = array('status_code' => -4, 'err_msg' => 'no offset parameter is given');
        }
    }
    else{ //    don't synchronize history articles, read url list from post data
        $urls_str = isset($_POST['given_urls']) ? $_POST['given_urls'] : '';
        if($urls_str != ''){
            $url_list = sync_wechat_split_url($urls_str);
            $config = sync_wechat_set_config();
            $return_array = sync_wechat_insert_by_urls($url_list, $config);
            if(isset($_POST['url_id']) && $config['debug']){
                $return_array['url_id'] = esc_textarea($_POST['url_id']);
            }
        }
        else{
            $return_array = array('status_code' => -9, 'err_msg' => 'no urls are given');
        }
    }
    echo json_encode($return_array);
    wp_die();
}
add_action( 'wp_ajax_sync_wechat_process_request', 'sync_wechat_process_request' );

?>
