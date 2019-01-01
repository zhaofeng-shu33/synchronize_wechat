<?php
/**
 * @package wechat_synchronize_api
 * @file synchronize_api.php
 */

require "wechat-php-sdk/autoload.php";
use Gaoming13\WechatPhpSdk;
use Gaoming13\WechatPhpSdk\Api;
use Gaoming13\WechatPhpSdk\Utils\HttpCurl;

require_once 'insert_by_url.php';
/**
 * \brief Custom functions to retrieve the access_token from the database
 * @param: void
 * @return valid access token string
 */
function ws_get_access_token(){
  // notice that the access_token is the serialized json string containing the expired time (UTC)
    return get_option('access_token');
}

/**
 * \brief Custom functions to save the access_token to the database
 * @param: access token string
 * @return void
 */
function ws_save_access_token($token){
    update_option('access_token', $token);
}

/**
 * @param: valid access token
 * @return url list of current wechat account prior to current date
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
    // each time maximal 20 articles fetch is allowed
    if($err){
        return array('post_id' => -1*$err->errcode, 'err_msg' => $err->errmsg);
    }
    $offset = 0;
    
    $url_list = array();
    while($offset < $data->news_count){ //
        list($err, $material) = $api->get_materials('news', $offset, 20);
        // extract urls of each article from $material list and append it to an array
        for($i=0; $i<count($material->item); $i++){ //
            $news_item = $material->item[$i]->content->news_item;
            for($j=0; $j<count($news_item); $j++){
                $url = $news_item[$j]->url;
                array_push($url_list, $url);
            }            
        }
        $offset += 20;
    }
    return $url_list;
}

function ws_process_request(){
    // if no post data, return 
    $sync_history = isset($_REQUEST['ws_history']) ? $_REQUEST['ws_history'] == 'ws_Yes' : false;
    if($sync_history){
            $return_array = ws_get_history_url();
    }
    else{
        $urls_str = isset($_REQUEST['given_urls']) ? $_REQUEST['given_urls'] : '';
        if($urls_str != ''){
            $url_list = explode("\n", $urls_str);
            // file_put_contents($file, '');                    
            $return_array = ws_insert_by_urls($url_list);
        }
        else{
            $return_array = array('post_id' => -9, 'err_msg' => 'no urls are given');
        }
    }
    echo json_encode($return_array);
    wp_die();
}

?>