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
function wsync_get_access_token(){
  // notice that the access_token is the serialized json string containing the expired time (UTC)
    return get_option('access_token');
}

/**
 * \brief Custom functions to save the access_token to the database
 * @param: access token string
 * @return void
 */
function wsync_save_access_token($token){
    update_option('access_token', $token);
}

/**
 * @param: valid access token
 * @return url list of current wechat account prior to current date
 */
function wsync_get_history_url(){
    $api = new Api(
	    array(
            'appId' => get_option('appid'),
            'appSecret'	=> get_option('appsecret'),
            'get_access_token' => 'wsync_get_access_token',
            'save_access_token' => 'wsync_save_access_token'
        )
    );
    list($err, $data) = $api->get_material_count();
    // each time maximal 20 news collection fetch is allowed
    if($err){
        return array('status_code' => -1*$err->errcode, 'err_msg' => $err->errmsg);
    }
    $offset = 0;
    
    $url_list = array();
    while($offset < $data->newsync_count){ //
        wsync_get_history_url_by_offset($offset, 20, $api);
        $offset += 20;
    }
    return $url_list;
}

function wsync_get_history_url_by_offset($offset, $num = 20, $api = null){
    if($api == null){
        $api = new Api(
            array(
                'appId' => get_option('appid'),
                'appSecret'	=> get_option('appsecret'),
                'get_access_token' => 'wsync_get_access_token',
                'save_access_token' => 'wsync_save_access_token'
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
        $news_item = $material->item[$i]->content->new_item;
        for($j=0; $j<count($news_item); $j++){
            $url = $news_item[$j]->url;
            array_push($url_list, $url);
        }            
    }
    return array('status_code' => 0, 'err_msg' => '', 'data' => $url_list);
}
?>