<?php
/**
 * @file insert_by_url.php
 */
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('simple_html_dom_node')){
	require_once("php-simple-html-dom/simple_html_dom.php");
}
//! \brief  check the domain name
//! \param  $url
//! \return  trimed $url if $url contains the domain name of wx; otherwise, empty string is returned
function sync_wechat_check_wx_url($url){
    if (strpos($url, 'http://mp.weixin.qq.com/s') !== false || strpos($url, 'https://mp.weixin.qq.com/s') !== false) {
        $url = str_replace('http://', 'https://', $url);
	    return trim($url);
    }
    else
        return '';
}
//! \brief  get the html from url
//! \param  $url
//! \return  $html raw text, no error handling in this function
function sync_wechat_get_html($url, $timeout = 30){
    //first check local copy
    $file_name = __DIR__ . '/asset/' . sha1($url);
    $html = '';
    if(file_exists($file_name)){
        $html = file_get_contents($file_name);
    }
    if(strpos($html, 'js_content') > 0 ){
        return $html;
    }
    $response = wp_safe_remote_get( $url, array( 'timeout' => $timeout) );
    $html = wp_remote_retrieve_body($response);
    file_put_contents($file_name, $html);
    return $html;
}

/**
* \brief this function insert wechat article to the wp-database
* this function relies on global variable $wpdb and the php module $curl
* \param $config
* \parblock
*
*     $config = {
* 		    'changeAuthor'[bool,default:false]:whether to keep the original author
*		    'changePostTime'[bool,default:false]: whether to keep the original post time
*		    'postStatus'[choice,default:publish]: article status
*           'postType'[choice,default:post]: article type
*		    'keepStyle'[bool,default:false]: whether the css of the article is kept
*           'keepSource'[bool, default:true]: whether the original source info is kept
*	    	'postCate'[choice,default:not_classcified]: the classification to put the article
*           'setFeatureImage'[bool,default:true]: whether to set the feature image
*           'debug'[bool, default:true]: whether to turn on debug mode, debug mode will output more detailed information
*     }
* \endparblock
* \return $status
* \parblock
*
*     $status = {
*         'status_code'[int]: if status_code = 0, error occurs
*         'err_msg'[str]: if no error, empty str
*     }
* \endparblock
*/
function sync_wechat_insert_by_url($url, $config = Null){
    $url = sync_wechat_check_wx_url($url);
    if (!$url) {
            return array('status_code' => -1, 'err_msg' => 'url does not contain mp.weixin.qq.com');
    }
    $html = sync_wechat_get_html($url);
    if (!$html) {
        return array('status_code' => -2, 'err_msg' => 'cannot get any message from '. $url);
    }
    return sync_wechat_insert_by_html($html, $config);
}
function sync_wechat_get_publish_date($html){
    preg_match('/(,n=")([^\"]+)",s=/', $html, $matches);
    $postDate = isset($matches[2]) ? $matches[2] : strtotime(current_time('timestamp'));
    $postDate = date('Y-m-d H:i:s', $postDate);
    return $postDate;
}
//! \brief insert $wpdb from html, called by ::sync_wechat_insert_by_url
function sync_wechat_insert_by_html($html, $config = Null){
    preg_match("/(msg_title = ')([^\"]+)'/", $html, $matches);
    // make sure the title of the article exists
    if (count($matches)==0) {
        return array('status_code' => -3, 'err_msg' => 'cannot get title from html');
    }
    $title = trim($matches[2]);
    // check whether the title is duplicate. If duplicate, return
    $post_id = post_exists($title);
    if ($post_id != 0) {
        //check whether post content is empty;
        if(strlen(get_post($post_id)->post_content)==0){
            return sync_wechat_set_image($html, $post_id, $config);
        }
        return array('status_code' => $post_id, 'err_msg' => 'the article is already in the database');
    }
    // publish date
    $changePostTime = isset($config['changePostTime']) && $config['changePostTime'];
    if ($changePostTime) {
        $postDate = date('Y-m-d H:i:s', current_time('timestamp'));
    } else {
        $postDate = sync_wechat_get_publish_date($html);
    }
    // whether to remove the original article style
    $keepStyle = isset($config['keepStyle']) && $config['keepStyle'];
    if (!$keepStyle) {
            $html = preg_replace('/style\=\"[^\"]*\"/', '', $html);
    }

    // default is draft
    $postStatus = isset($config['postStatus']) && in_array($config['postStatus'], array('publish', 'pending', 'draft')) ?
                                $config['postStatus'] : 'draft';
    // article type, default is post
    $postType       = isset($config['postType']) ? $config['postType'] : 'post';
    // whether to change author, not implemented yet
    $changeAuthor   = isset($config['changeAuthor']) && $config['changeAuthor'];
    if ($changeAuthor){
        // todo
    } else {
        $userId = get_current_user_id();
    }
    // article category, default is Uncategorized
    $cates = isset($config['postCate'])? $config['postCate'] : '';
    if ($cates) {
        $cateIds = array();
        foreach ($cates as $cate) {
                $term = get_term_by('name', $cate, 'category');
                if ($term) {
                        $cateIds[] = $term->term_id;
                } else {
                }
        }
        $postCate = $cateIds;
}
    else{
        $postCate = array(1);
    }
    $post = array(
            'post_title'    => $title,
            'post_content'  => '',
            'post_status'   => $postStatus,
            'post_date'     => $postDate,
            'post_modified' => $postDate,
            'post_author'   => $userId,
            'post_category' => $postCate,
            'post_type'	    => $postType
    );
    $postId = wp_insert_post($post);
    if(is_wp_error($postId)){
        return array('status_code' => -8, 'err_msg' => $postId->get_error_message());
    }
    return sync_wechat_set_image($html, $postId, $config);
}

//! \brief  get 1. attached image for $postId, 2. unattached image, and check whether image_name is within it
//! \param  postId post Id
//! \param image_name image_name to be checked
//! \return  $status
//! `{'status_code':$postId, 'err_msg':$err_msg}`
//! if the image exists, set $postId = image attachment id, otherwise set postId = 0
function sync_wechat_check_image_exists($postId, $image_name){
    $media_array_unattached = get_children(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image'
        ));
    $media_array = array_merge($media_array_unattached, get_attached_media('image', $postId));

	foreach ($media_array as $media_object) {
        $attached_image_id = $media_object->ID;
        try{
            $relative_file_path = wp_get_attachment_metadata($attached_image_id)['file'];
        }
        catch(Exception $e){ //  wp_get_attachment_metadata($attached_image_id) may be empty, pass this case
            continue;
        }
        if(strstr(basename($relative_file_path), $image_name)){
            return array('status_code' => $attached_image_id, 'err_msg' => 'image already exists');
        }
	}
    return array('status_code' => 0, 'err_msg' => '');
}

//! \brief  guess the image extension from the url
//! \param  $url: image url
//! \return  extension name with the dot
function sync_wechat_get_image_extension_from_url($url){
  $extension = strstr(basename(explode('?', $url)[0]), '.');
  if($extension == false){
      preg_match('/wx_fmt=([a-z]+)/', $url, $matches);
      if(count($matches)==2)
        $extension = '.' . $matches[1];
  }
  return $extension;
}
//! \brief  use file content hash to generate image name
//!         the image suffix is got from the imageFile.
//! \param  $url: absolute url of the image file
//! \param  $prefix: prefix to prepend before the image name
//! \return  image file name, no error handling.
function sync_wechat_get_image_name($url, $prefix, $extension_=Null){
    $check_sum = sha1($url);
    $extension = sync_wechat_get_image_extension_from_url($url);
    if($extension == false)
        $extension = '.jpeg';
	$fileName = $prefix  . $check_sum . $extension;
    return $fileName;
}
//! \brief  download image from $url and update it for the post with id = $postId
//! \param  $url: wechat image original url
//! \param  $postId: post Id
//! \return $status
//! `status = {'status_code':$postId, 'err_msg':$err_msg}`
function sync_wechat_upload_image($url, $postId, $image_name = Null){
    if($image_name == Null){
		$prefixName = get_option('sync_wechat_image_name_prefix', 'ws-plugin-');
        $fileName = sync_wechat_get_image_name($url, $prefixName);
    }
    else{
        $fileName = $image_name;
    }
    $return_array = sync_wechat_check_image_exists($postId, explode('.', $fileName)[0]);
    $r_post_id = $return_array['status_code'];
    if($r_post_id > 0){
        if(wp_get_post_parent_id($r_post_id) ==0){
            wp_update_post($r_post_id, array('post_parent' => $postId));
        }
        return $return_array;
    }
	$tmpFile = download_url($url, 10);
	if (is_string($tmpFile)) {
		$fileArr  = array(
			'name'     => $fileName,
			'tmp_name' => $tmpFile
		);
        if($r_post_id == 0){
	    $return_obj = media_handle_sideload($fileArr, $postId);
            @unlink($tmpFile);
            if (!is_wp_error($return_obj)) { // upload sucessfully
                return array('status_code' => $return_obj, 'err_msg' => 'upload successfully');
	    }
            else{ // upload failed, $return_obj is instance of WP_Error
                return array('status_code' => -6, 'err_msg' => $return_obj->get_error_message(), 'err_url' => $url);
            }
        }
        else{ // image already exists
            @unlink($tmpFile);
            return $return_array;
        }
	}
    else{
        return array('status_code' => -5, 'err_msg' => 'download image failed');
    }
}
//! \brief: set the thumbnail image for the specified post, called by ::sync_wechat_set_image
function sync_wechat_set_feature_image($postId, $feature_image_url, $imageName = Null){
    if(has_post_thumbnail($postId)){
        return array('status_code' => get_post_thumbnail_id($postId), 'err_msg' => 'post already has thumbnail');
    }
    $return_array = sync_wechat_upload_image($feature_image_url, $postId, $imageName);
    if($return_array['status_code'] > 0){
        $post_meta_id = set_post_thumbnail($postId, $return_array['status_code']);
        if($post_meta_id == 0){
            return array('status_code' => $post_meta_id, 'err_msg' => 'set post thumbnail failed');
        }
        else{
            return array('status_code' => $post_meta_id, 'err_msg' => '');
        }
    }
    else{
        return $return_array;
    }
}
//! \brief  extract image urls from html, and download it to local file system, update image url in postId->postContent
//! \param  $html: raw html text, $postId: post Id
//! \return  status = {'status_code':$postId, 'err_msg':$err_msg}
function sync_wechat_set_image($html, $postId, $config = Null){
    // set featured image
    $setFeatureImage = isset($config['setFeatureImage']) ? $config['setFeatureImage'] : true;
    if ($setFeatureImage) {
        preg_match('/(msg_cdn_url = ")([^\"]+)"/', $html, $matches);
        sync_wechat_set_feature_image($postId, $matches[2]);
    }
    // process images(tested)
    $dom  = str_get_html($html);
    $imageDoms = $dom->find('img');
    foreach ($imageDoms as $imageDom) {
        $dataSrc = $imageDom->getAttribute('data-src');
        if (!$dataSrc) {
            continue;
        }
        $imageDom->setAttribute('src', $dataSrc);
    }

    sync_wechat_process_video($dom);

    // images must be downloaded to local file system
    return sync_wechat_download_image($postId, $dom, $config);
}

//! \brief insert url list into $wpdb, calling ::sync_wechat_insert_by_url
function sync_wechat_insert_by_urls($urls, $config) {
    foreach ($urls as $url) {
        $return_array = sync_wechat_insert_by_url($url, $config);
        if($return_array['status_code'] <= 0)
            return $return_array;
    }
    return $return_array;
}
//! \brief resolve css background images, called by ::sync_wechat_download_image
function sync_wechat_resolve_bg_image(&$content, $postId){
    $re = '/background-image: url\(&quot;([^&]+)&quot;\)/';
    preg_match($re, $content, $matches);
    while(count($matches)==2){
        $image_url = $matches[1];
        $return_array = sync_wechat_upload_image($image_url, $postId);
        $id = $return_array['status_code'];
        $imageInfo = wp_get_attachment_image_src($id, 'full');
        $src       = $imageInfo[0];
        $content = preg_replace($re, 'background-image: url(&qquot;'. $src . '&qquot;)', $content, 1);
        $matches = array();
        preg_match($re, $content, $matches);
    }
    $content = str_replace('&qquot;', '&quot;', $content);
}
//! \brief resolve article origin, called by ::sync_wechat_download_image
function sync_wechat_resolve_origin($dom){
    $origin = $dom->find('#profileBt a', 0);
    if($origin){
        $origin = $origin->plaintext;
    }
    else{ // handle republish
         $origin = $dom->find('.original_account_nickname', 0)->plaintext;
    }
    $origin = trim(esc_html($origin));
    return $origin;
}
//! \brief resolve tencent video, add width, height, src attribute
function sync_wechat_process_video(&$dom){
    $videos            = $dom->find('.video_iframe');
    foreach($videos as $video){
        // Due to wechat cross-origin restriction, video can
        // not be played on external website. So we delete the
        // dom completely
        $video->clear();
    }
    return;
}
//! \brief download all images in $dom, called by ::sync_wechat_set_image
function sync_wechat_download_image($postId, $dom, $config = Null) {
	$images            = $dom->find('img');
	$centeredImage     = get_option('sync_wechat_image_centered', 'no') == 'yes';

    foreach ($images as $image) {
        $src  = $image->getAttribute('src');
        if (!$src) {
                continue;
        }
        if (strstr($src, 'res.wx.qq.com')) {
                continue;
        }
        if ($centeredImage) {
        $class = $image->getAttribute('class');
                $class .= ' aligncenter';
                $image->setAttribute('class', $class);
        }
        $src = preg_replace('/^\/\//', 'http://', $src, 1);
        $return_array = sync_wechat_upload_image($src, $postId);
        $id = $return_array['status_code'];
        if($id < 0){
            if($config['debug']){
                $return_array['article_id'] = $postId;
                return $return_array;
            }
            else
                continue;
        }
        else { // amend the url
            $imageInfo = wp_get_attachment_image_src($id, 'full');
            $src       = $imageInfo[0];
            $image->setAttribute('src', $src);
        }
    }
    // resolve css background images
    $content = $dom->find('#js_content', 0)->innertext;
    sync_wechat_resolve_bg_image($content, $postId);
    $origin = sync_wechat_resolve_origin($dom);


    $keepSource = isset($config['keepSource']) ? $config['keepSource'] : true;
    if ($keepSource) {
        $source = "<blockquote class='keep-source'>" .
                        "<p>始发于微信公众号：{$origin}</p>" .
                        "</blockquote>";
        $content .= $source;
    }
    $content = trim($content);
    $postArray = array(
            'ID' => $postId,
            'post_content' =>  $content
    );
    $return_postID = wp_update_post($postArray);
    if(is_wp_error($return_postID)){
        return array('status_code' => -7, 'err_msg' => $return_postID->get_error_message());
    }
    return array('status_code' => $postId, 'err_msg' => 'create post successfully');
}
?>
