<?php
/**
 * @package wechat_synchronize_api
 * @file synchronize_api.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
require "wechat-php-sdk/autoload.php";
use Gaoming13\WechatPhpSdk;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\HttpCurl;

/**
 * \brief Custom functions to retrieve the access_token from the database
 * @param: void
 * @return valid access token string
 */
function sync_wechat_get_access_token(){
  // notice that the access_token is the serialized json string containing the expired time (UTC)
    return get_option('access_token');
}

/**
 * \brief Custom functions to save the access_token to the database
 * @param: access token string
 * @return void
 */
function sync_wechat_save_access_token($token){
    update_option('access_token', $token);
}

/**
 * @param: $offset
 * @param: $num number of items to get
 * @return $return_array
 */
function sync_wechat_get_history_url_by_offset($offset, $num = 20, $api = null){
    if($api == null){
        $api = new Api(
            array(
                'appId' => get_option('appid'),
                'appSecret'	=> get_option('appsecret'),
                'get_access_token' => 'sync_wechat_get_access_token',
                'save_access_token' => 'sync_wechat_save_access_token'
            )
        );    
    }
    $url_list = array(); 
    list($err, $material) = $api->get_materials('news', $offset, $num);
    if($err){
        return array('status_code' => -1*$err->errcode, 'err_msg' => $err->errmsg);
    }
    // extract urls of each article from $material list and append it to an array
    for($i=0; $i<count($material->item); $i++){ //
        $news_item = $material->item[$i]->content->news_item;
        for($j=0; $j<count($news_item); $j++){
            $url = $news_item[$j]->url;
            array_push($url_list, $url);
        }            
    }
    $latest_update_time = $material->item[0]->update_time;
    return array('status_code' => 0, 'err_msg' => '',
        'data' => array('url_list' => $url_list, 'latest_update_time' => $latest_update_time)
    );
}

function sync_wechat_get_latest_post_publish_date(){
    $arg = array(
        'numberposts' => 1,
        'post_type' => 'post'
    );
    $result_posts = wp_get_recent_posts($args);
    $latest_post_date_string = $result_posts[0]->post_date;
    return $latest_post_date_string;
}
?>