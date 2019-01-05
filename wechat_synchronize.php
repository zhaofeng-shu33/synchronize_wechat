<?php
/*
Plugin Name: wechat synchronize
Plugin URI: https://github.com/zhaofeng-shu33/wechat_synchronize_to_wordpress
Description: synchronize wechat articles to wordpress website
Author: zhaofeng-shu33
Version: 0.3
Author URI: https://github.com/zhaofeng-shu33
*/
/**
 * @file wechat_synchronize.php
 */
require_once "synchronize_api.php";
require_once 'insert_by_url.php';
if ( ! defined( 'ABSPATH' ) ) exit;

if (is_admin()) {
	add_action('admin_menu', 'wsync_admin_menu');
}

//! \brief initialize admin menu as submenu under **Settings**
function wsync_admin_menu(){
    add_options_page('ws options', 'ws', 'manage_options', 'ws-unique-identifier', 'wsync_plugin_options');
    add_action('admin_init', 'wsync_register_settings');
}

//! \brief register setting data for persistent storage
function wsync_register_settings(){
    register_setting('ws-settings-group', 'appid');
    register_setting('ws-settings-group', 'appsecret');
    add_option('access_token');
}

//! \brief  load the frontend page
function wsync_plugin_options(){
    require_once 'setting-page.php';
}

//! \brief  basic config setting function
function wsync_set_config(){
    $changeAuthor   = false;
    $changePostTime = isset($_POST['change_post_time']) && $_POST['change_post_time'] == 'true';
    $postStatus     = isset($_POST['post_status']) && in_array($_POST['post_status'], array('publish', 'pending', 'draft')) ?
                                            $_POST['post_status'] : 'publish';
    $keepStyle      = isset($_POST['keep_style']) && $_POST['keep_style'] == 'keep';
    $keepSource      = isset($_POST['keep_source']) ? $_POST['keep_source'] == 'keep': true;    
    $postCate       = isset($_POST['post_cate']) ? intval($_POST['post_cate']) : 1;
    $postCate       = array($postCate);
    $postType       = isset($_POST['post_type']) ? $_POST['post_type'] : 'post';
	$debug          = isset($_POST['debug']) ? $_POST['debug'] == 'on' : true;
    $config = array(
		'changeAuthor'    => $changeAuthor,
		'changePostTime'  => $changePostTime,
		'postStatus'   => $postStatus,
        'postType' => $postType,
		'keepStyle'     => $keepStyle,
        'keepSource' => $keepSource,
		'postCate' => $postCate,
        'setFeatureImage' => true,
        'debug' => $debug
    );    
    return $config;
}

//! \brief ajax callback main function
function wsync_process_request(){
    $sync_history = isset($_POST['wsync_history']) ? $_POST['wsync_history'] == 'wsync_Yes' : false;
    if($sync_history){
        if(isset($_POST['offset'])){
            $return_array = array();
            $num = isset($_POST['num']) ? intval($_POST['offset']) : 20;
            wsync_get_history_url_by_offset($return_array, $_POST['offset'], $num);            
        }
        else{
            $return_array = wsync_get_history_url();
        }
    }
    else{ //    not synchronize history articles, read url list from post data
        $urls_str = isset($_POST['given_urls']) ? esc_url($_POST['given_urls']) : '';
        if($urls_str != ''){
            $url_list = explode("\n", $urls_str);
            $config = wsync_set_config();
            $return_array = wsync_insert_by_urls($url_list, $config);
        }
        else{
            $return_array = array('post_id' => -9, 'err_msg' => 'no urls are given');
        }
        if(isset($_POST['url_id'])){
            $return_array['url_id'] = $_POST['url_id'];
        }
    }
    echo json_encode($return_array);
    wp_die();
}
add_action( 'wp_ajax_wsync_process_request', 'wsync_process_request' );

?>
