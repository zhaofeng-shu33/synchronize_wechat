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
 * @param: $max_num number of items to get
 * @param: $date_check if True, do the date check and may return less urls.
 * @return $return_array
 */
function sync_wechat_get_history_url_by_offset($offset, $max_num = 20, $api = null, $date_check = True){
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
    list($err, $material) = $api->get_materials('news', $offset, $max_num);
    if($err){
        return array('status_code' => -1*$err->errcode, 'err_msg' => $err->errmsg);
    }
    $num_material_item = count($material->item);
    $need_update = True;
    if($date_check){
        $latest_article_post_time = sync_wechat_get_latest_post_publish_date(); //timestamp
        for($i=0; $i<count($material->item); $i++){
            if($material->item[$i]->update_time <= $latest_article_post_time){
                $num_material_item = $i;
                $need_update = False;
                break;
            }
        }
    }
    // extract urls of each article from $material list and append it to an array
    for($i = 0; $i < $num_material_item; $i++){ //
        $news_item = $material->item[$i]->content->news_item;
        for($j=0; $j<count($news_item); $j++){
            $url = $news_item[$j]->url;
            array_push($url_list, $url);
        }            
    }
    return array('status_code' => 0, 'err_msg' => '',
        'data' => array('url_list' => $url_list, 'need_update' => $need_update)
    );
}

function sync_wechat_split_url($url_list_string){
    $url_list = explode("\n", $url_list_string);
    return $url_list;
}

/*
* @return $timestamp time stamp of the latest post article
*/
function sync_wechat_get_latest_post_publish_date(){
    $args = array(
        'numberposts' => 1,
        'post_type' => 'post',
        'post_status' => 'publish'
    );
    $result_posts = wp_get_recent_posts($args);
    $latest_post_date_string = $result_posts[0]['post_date'];
    return strtotime($latest_post_date_string);
}
?>