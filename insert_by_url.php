<?php
/**
 * @version 1.0
 * \file insert_by_url.php
 */
if(!class_exists('simple_html_dom_node')){
	require_once("php-simple-html-dom/simple_html_dom.php");
}
//! \brief  check the domain name
//! \param  $url
//! \return  trimed $url if $url contains the domain name of wx; otherwise, empty string is returned
function check_wx_url($url){
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
function get_html($url, $timeout = 30){
    $response = wp_safe_remote_get( $url, array( 'timeout' => $timeout) );    
    return wp_remote_retrieve_body($response);
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
*     }
* \endparblock
* \return $status
* \parblock
*
*     $status = {
*         'post_id'[int]: if post_id = 0, error occurs
*         'err_msg'[str]: if no error, empty str      
*     }
* \endparblock
*/
function ws_insert_by_url($url, $config = Null){
    $url = check_wx_url($url);
    if (!$url) {
            return array('post_id' => -1, 'err_msg' => 'url does not contain mp.weixin.qq.com');
    }
    $html = get_html($url);
    if (!$html) {
        return array('post_id' => -2, 'err_msg' => 'cannot get any message from '. $url);
    }
    return ws_insert_by_html($html, $config);
}
function get_publish_date($html){
    preg_match('/(publish_time = ")([^\"]+)"/', $html, $matches);
    $postDate = isset($matches[2]) ? $matches[2] : current_time('timestamp');
    $postDate = date('Y-m-d H:i:s', strtotime($postDate));
    return $postDate;
}
//! \brief insert $wpdb from html, called by ::ws_insert_by_url
function ws_insert_by_html($html, $config = Null){
    preg_match('/(msg_title = ")([^\"]+)"/', $html, $matches);
    // make sure the title of the article exists
    if (count($matches)==0) {
        return array('post_id' => -3, 'err_msg' => 'cannot get title from html');
    }
    $title = trim($matches[2]);
    // check whether the title is duplicate. If duplicate, return
    $post_id = post_exists($title);
    if ($post_id != 0) {
        //check whether post content is empty;
        if(strlen(get_post($post_id)->post_content)==0){
            return ws_set_image($html, $postId, $config);            
        }
        return array('post_id' => $post_id, 'err_msg' => 'the article is already in the database');
    }
    // publish date
    $changePostTime = isset($config['changePostTime']) && $config['changePostTime'];
    if ($changePostTime) {
        $postDate = date('Y-m-d H:i:s', current_time('timestamp'));
    } else {
        $postDate = get_publish_date($html);
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
        return array('post_id' => -8, 'err_msg' => $postId->get_error_message());
    }
    return ws_set_image($html, $postId, $config);
}

//! \brief  get all attached image for $postId and check whether image_name is within it
//! \param  postId post Id
//! \param image_name image_name to be checked
//! \return  $status
//! `{'post_id':$postId, 'err_msg':$err_msg}`
//! if the image exists, set $postId = image attachment id, otherwise set postId = 0
function ws_check_image_exists($postId, $image_name){
    $media_array_unattached = get_children(array(
        'post_type' => 'attachment',
        'post_mime_type' => 'image'
        ));
    $media_array = array_merge($media_array_unattached, get_attached_media('image', $postId));
	foreach ($media_array as $media_object) {
        $attached_image_id = $media_object->ID;
        $relative_file_path = wp_get_attachment_metadata($attached_image_id)['file'];
        if(strstr(basename($relative_file_path), $image_name)){
            return array('post_id' => $attached_image_id, 'err_msg' => 'image already exists');
        }
	}
    return array('post_id' => 0, 'err_msg' => '');
}

//! \brief  guess the image extension from the url
//! \param  $url: image url
//! \return  extension name with the dot
function _get_image_extension_from_url($url){
  $extension = strstr(basename($url), '.');
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
function _get_image_name($url, $prefix, $extension_=Null){
    $check_sum = sha1($url);
    $extension = _get_image_extension_from_url($url);
    if($extension == false)
        $extension = '.jpeg';
	$fileName = $prefix  . $check_sum . $extension;
    return $fileName;
}
//! \brief  download image from $url and update it for the post with id = $postId
//! \param  $url: wechat image original url
//! \param  $postId: post Id
//! \return $status
//! `status = {'post_id':$postId, 'err_msg':$err_msg}`
function ws_upload_image($url, $postId, $image_name = Null){
    if($image_name == Null){
		$prefixName = get_option('ws_image_name_prefix', 'ws-plugin-');
        $fileName = _get_image_name($url, $prefixName);
    }
    else{
        $fileName = $image_name;
    }
    $return_array = ws_check_image_exists($postId, explode('.', $fileName)[0]);
    $r_post_id = $return_array['post_id'];
    if($r_post_id > 0){
        if(wp_get_post_parent_id($r_post_id) ==0){
            wp_update_post($r_post_id, array('post_parent' => $postId));
        }
        return $return_array;
    }
	$tmpFile = download_url($url);
	if (is_string($tmpFile)) {
		$fileArr  = array(
			'name'     => $fileName,
			'tmp_name' => $tmpFile
		);
        if($r_post_id == 0){
	    $return_obj = media_handle_sideload($fileArr, $postId);
            @unlink(tmpFile);
            if (!is_wp_error($return_obj)) { // upload sucessfully
                return array('post_id' => $return_obj, 'err_msg' => 'upload successfully');
	    }
            else{ // upload failed, $return_obj is instance of WP_Error
                return array('post_id' => -6, 'err_msg' => $return_obj->get_error_message());
            }
        }
        else{ // image already exists
            @unlink(tmpFile);
            return $return_array;
        }
	}
    else{
        return array('post_id' => -5, 'err_msg' => 'download feature image failed');
    }
}
//! \brief: set the thumbnail image for the specified post, called by ::ws_set_image
function ws_set_feature_image($postId, $feature_image_url, $imageName = Null){
    if(has_post_thumbnail($postId)){
        return array('post_id' => get_post_thumbnail_id($postId), 'err_msg' => 'post already has thumbnail');
    }
    $return_array = ws_upload_image($feature_image_url, $postId, $imageName);
    if($return_array['post_id'] > 0){
        $post_meta_id = set_post_thumbnail($postId, $return_array['post_id']);
        if($post_meta_id == 0){
            return array('post_id' => $post_meta_id, 'err_msg' => 'set post thumbnail failed');
        }
        else{
            return array('post_id' => $post_meta_id, 'err_msg' => '');
        }
    }
    else{
        return $return_array;
    }
}
//! \brief  extract image urls from html, and download it to local file system, update image url in postId->postContent
//! \param  $html: raw html text, $postId: post Id
//! \return  status = {'post_id':$postId, 'err_msg':$err_msg}
function ws_set_image($html, $postId, $config = Null){
    // set featured image
    $setFeatureImage = isset($config['setFeatureImage']) ? $config['setFeatureImage'] : true;
    if ($setFeatureImage) {
        preg_match('/(msg_cdn_url = ")([^\"]+)"/', $html, $matches);
        ws_set_feature_image($postId, $matches[2]);
    }
    // process images(tested)
    $dom  = str_get_html($html);
    $imageDoms = $dom->find('img');
    $sprindboard = 'http://read.html5.qq.com/image?src=forum&q=4&r=0&imgflag=7&imageUrl=';
    foreach ($imageDoms as $imageDom) {
        $dataSrc = $imageDom->getAttribute('data-src');
        if (!$dataSrc) {
            continue;
        }
        $src  = $sprindboard . $dataSrc;
        $imageDom->setAttribute('src', $src);
    }
    // video cannot simply be done, tencent video api has not been documented
    // this feature is not implemented.
    $videoDoms = $dom->find('.video_iframe');
    foreach ($videoDoms as $videoDom) {
        $videoDom->clear();
        // $dataSrc = $videoDom->getAttribute('data-src');
        // $videoDom->setAttribute('src', $dataSrc);
    }
    // images must be downloaded to local file system
    return ws_download_image($postId, $dom, $config);
}

//! \brief insert url list into $wpdb, calling ::ws_insert_by_url
function ws_insert_by_urls($urls) {
    $changeAuthor   = false;
    $changePostTime = isset($_REQUEST['change_post_time']) && $_REQUEST['change_post_time'] == 'true';
    $postStatus     = isset($_REQUEST['post_status']) && in_array($_REQUEST['post_status'], array('publish', 'pending', 'draft')) ?
                                            $_REQUEST['post_status'] : 'publish';
    $keepStyle      = isset($_REQUEST['keep_style']) && $_REQUEST['keep_style'] == 'keep';
    $keepSource      = isset($_REQUEST['keep_source']) ? $_REQUEST['keep_source'] == 'keep': true;    
    $postCate       = isset($_REQUEST['post_cate']) ? intval($_REQUEST['post_cate']) : 1;
    $postCate       = array($postCate);
    $postType       = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : 'post';
	
    $config = array(
		'changeAuthor'    => $changeAuthor,
		'changePostTime'  => $changePostTime,
		'postStatus'   => $postStatus,
        'postType' => $postType,
		'keepStyle'     => $keepStyle,
        'keepSource' => $keepSource,
		'postCate' => $postCate,
        'setFeatureImage' => true
    );
    foreach ($urls as $url) {
        $return_array = ws_insert_by_url($url, $config);
        if($return_array['post_id'] <= 0)
            return $return_array;
    }
    return $return_array;
}
//! \brief resolve css background images, called by ::ws_download_image
function ws_resolve_bg_image(&$content, $postId){
    $re = '/background-image: url\(&quot;([^&]+)&quot;\)/';
    preg_match($re, $content, $matches);
    while(count($matches)==2){
        $image_url = $matches[1];
        $return_array = ws_upload_image($image_url, $postId);
        $id = $return_array['post_id'];
        $imageInfo = wp_get_attachment_image_src($id, 'full');
        $src       = $imageInfo[0];   
        $content = preg_replace($re, 'background-image: url(&qquot;'. $src . '&qquot;)', $content, 1);
        $matches = array();
        preg_match($re, $content, $matches);
    }
    $content = str_replace('&qquot;', '&quot;', $content);    
}
//! \brief resolve article origin, called by ::ws_download_image
function ws_resolve_origin($dom){
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
//! \brief download images in $dom, called by ::ws_set_image
function ws_download_image($postId, $dom, $config = Null) {
	$images            = $dom->find('img');
	$centeredImage     = get_option('ws_image_centered', 'no') == 'yes';
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
        $return_array = ws_upload_image($src, $postId);
        $id = $return_array['post_id'];
        if($id < 0){
            if(isset($_REQUEST['debug']) && $_REQUEST['debug'] == 'on' ){
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
    ws_resolve_bg_image($content, $postId);
    $origin = ws_resolve_origin($dom);

    
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
        return array('post_id' => -7, 'err_msg' => $return_postID->get_error_message());
    }
    return array('post_id' => $postId, 'err_msg' => 'create post successfully');    
}
?>