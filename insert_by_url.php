<?php
if(!class_exists('simple_html_dom_node')){
	require_once("php-simple-html-dom/simple_html_dom.php");
}
//! \brief: check the domain name
//! input: $url
//! output: trimed $url if $url contains the domain name of wx; otherwise, empty string is returned
function check_wx_url($url){
    if (strpos($url, 'http://mp.weixin.qq.com/s') !== false || strpos($url, 'https://mp.weixin.qq.com/s') !== false) {
        $url = str_replace('http://', 'https://', $url);
	    return trim($url);
    }
    else
        return '';
}
//! \brief: get the html from url
//! input: $url
//! output: $html raw text, no error handling in this function
function get_html($url, $timeout = 30){
  
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$html = curl_exec($ch);
	curl_close($ch);
    
    return $html;
}
//! \brief: create new user and add its role as contributor
//! input: name and password for the new user
//! output: newly created user id
//! status: not tested
function create_new_user($name, $pwd){
    $userId   = wp_create_user($name, $pwd);
    // 用户已存在
    if($userId){
        if ($userId->get_error_code() == 'existing_user_login') {
            $userData = get_user_by('login', $bizVal);
        } else if(is_integer($userId) > 0) {
            $userData = get_userdata($userId);
        } else {
            // 错误情况, return invalid user_id
            return 0;
        }
        // 默认是投稿者
        $userData->add_role('contributor');
        $userData->remove_role('subscriber');
        $userData->display_name = $userName;
        $userData->nickname     = $userName;
        $userData->first_name   = $userName;
        wp_update_user($userData);
        $userId = $userData->ID;
    } else {
        // 默认博客作者
        $userId = get_current_user_id();
    }
    return $userId;
}
/**
* intro: this function insert wechat article to the wp-database
* this function relies on global variable $wpdb and the php module $curl
* input:
* $config = {
* 		'changeAuthor'[bool,default:false]:whether to keep the original author
*		'changePostTime'[bool,default:false]: whether to keep the original post time
*		'postStatus'[choice,default:draft]: article status
*       'postType'[choice,default:post]: article type
*		'keepStyle'[bool,default:false]: whether the css of the article is kept
*		'postCate'[choice,default:not_classcified]: the classification to put the article
*       'downloadImage'[bool,default:false]: whether download image and save a local copy
*    }
* returns: 
* status = {
*     'post_id'[int]: if post_id = 0, error occurs
*     'err_msg'[str]: if no error, empty str      
*   }
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

function ws_insert_by_html($html, $config = Null){
		// 是否移除原文样式
        $keepStyle = isset($config['keep_style']) && $config['keep_style'];
		if (!$keepStyle) {
			$html = preg_replace('/style\=\"[^\"]*\"/', '', $html);
		}
		// 文章标题
		preg_match('/(msg_title = ")([^\"]+)"/', $html, $matches);
        // 确保有标题
		if (count($matches)==0) {
			return array('post_id' => -3, 'err_msg' => 'cannot get title from html');
		}
		$title = trim($matches[2]);
		// 同步任务检查标题是否重复，若重复则跳过
        $post_id = post_exists($title);
		if ($post_id != 0) {
			return array('post_id' => $post_id, 'err_msg' => 'the article is already in the database');
		}

		// 发布日期
        $changePostTime = isset($config['changePostTime']) && $config['changePostTime'];
		if ($changePostTime) {
			$postDate = date('Y-m-d H:i:s', current_time('timestamp'));
		} else {
			preg_match('/(publish_time = ")([^\"]+)"/', $html, $matches);
			$postDate = isset($matches[2]) ? $matches[2] : current_time('timestamp');
			$postDate = date('Y-m-d H:i:s', strtotime($postDate));
		}
    	// 默认是存为草稿
	    $postStatus = isset($config['postStatus']) && in_array($config['postStatus'], array('publish', 'pending', 'draft')) ?
					$config['postStatus'] : 'draft';

	    // 文章类型，默认是post
	    $postType       = isset($config['postType']) ? $config['postType'] : 'post';

		// 是否改变作者，如改变则新建，不改变则默认是当前登录作者
        $changeAuthor   = isset($config['changeAuthor']) && $config['changeAuthor'];
		if ($changeAuthor){
			// 创建用户
            $userName = $dom->find('#post-user', 0)->plaintext;
            $userName = esc_html($userName);
            $userId = create_new_user($userName, crc32($userName));   
            if(!$userId){
                return array('post_id' => 0, 'err_msg' => 'error occurs when create new user with userName = ' . $userName);
            }
		} else {
			// 默认博客作者
			$userId = get_current_user_id();
		}
	    // 文章分类，默认是未分类（1）
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
	        $postCate       = array(1);        
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
        $postId         = null;
		$postId = wp_insert_post($post);
        if(is_wp_error($postId)){
            return array('post_id' => -8, 'err_msg' => $postId->get_error_message());
        }
        $setFeaturedImage  = get_option('ws_featured_image', 'yes') == 'yes';
        return ws_set_image($html, $postId, $setFeaturedImage);
}

//! \brief: get all attached image for $postId and check whether image_name is within it
//! input: $postId: post Id
//!        $image_name: image_name to be checked
//! output: status = {'post_id':$postId, 'err_msg':$err_msg}, if the image exists, set $postId = image attachment id, otherwise set postId=0
function ws_check_image_exists($postId, $image_name){
    $media_array = get_attached_media('image', $postId);
	foreach ($media_array as $media_object) {
        $attached_image_id = $media_object->ID;
        $relative_file_path = wp_get_attachment_metadata($attached_image_id)['file'];
        if(strstr(basename($relative_file_path), $image_name)){
            return array('post_id' => $attached_image_id, 'err_msg' => 'image already exists');
        }
	}
    return array('post_id' => 0, 'err_msg' => '');
}
//! \brief: guess the image extension from the url
//! input: $url: image url
//! output: extension name with the dot
function get_image_extension_from_url($url){
  $extension = strstr(basename($url), '.');
  if($extension == false){
      preg_match('/wx_fmt=([a-z]+)/', $url, $matches);
      if(count($matches)==2)
        $extension = '.' . $matches[1];
  }
  return $extension;
}
//! \brief: use file content hash to generate image name
//!         the image suffix is got from the imageFile.
//! input: $imageFile: absolute path of the image file
//!        $prefix: prefix to prepend before the image name
//!        $extension_[optinal]: suffix of the image
//! output: image file name, no error handling.
function _get_image_name($imageFile, $prefix, $extension_=Null){
    $check_sum = sha1_file($imageFile);
    $extension = strstr(basename($imageFile), '.');
    if($extension_)
        $extension = $extension_;
    elseif($extension == false || $extension == '.tmp')
        $extension = '.jpeg';
	$fileName = $prefix  . $check_sum . $extension;
    return $fileName;
}
//! \brief: download image from $url and update it for the post with id = $postId
//! input: $url: wechat image original url
//!        $postId: post Id
//! output: status = {'post_id':$postId, 'err_msg':$err_msg}
function ws_upload_image($url, $postId, $image_name = Null){
    if($image_name != Null){
        $return_array = ws_check_image_exists($postId, $image_name);
        if($return_array['post_id'] > 0)
            return $return_array;
    }
	$tmpFile = download_url($url);
	if (is_string($tmpFile)) {
        if($image_name){
            $fileName = $image_name;
        }
        else{
		    $prefixName = get_option('ws_image_name_prefix', 'ws-plugin-');
            $extension = get_image_extension_from_url($url);
		    $fileName = _get_image_name($tmpFile, $prefixName, $extension);
            $return_array = ws_check_image_exists($postId, $fileName);
            if($return_array['post_id'] > 0)
                return $return_array;
        }
		$fileArr  = array(
			'name'     => $fileName,
			'tmp_name' => $tmpFile
		);
        if($return_array['post_id'] == 0){
		    $return_obj = media_handle_sideload($fileArr, $postId);
            @unlink(tmpFile);
		    if (!is_wp_error($return_obj)) { // upload sucessfully
                 return array('post_id' => $return_obj, 'err_msg' => 'upload successfully');
			    // @set_post_thumbnail($postId, $id);
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
function ws_set_feature_image($postId, $feature_image_url, $imageName = Null){
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
//! \brief: extract image urls from html, and download it to local file system, update image url in postId->postContent
//! input: $html:raw html text, $postId: post Id
//! output: status = {'post_id':$postId, 'err_msg':$err_msg}
function ws_set_image($html, $postId, $setFeaturedImage = false){
    	// 公众号设置 featured image
		if ($setFeaturedImage) {
			preg_match('/(msg_cdn_url = ")([^\"]+)"/', $html, $matches);
            ws_set_feature_image($postId, $matches[2]);
		}
		// 处理图片及视频资源
        $dom  = str_get_html($html);
		$imageDoms = $dom->find('img');
		$videoDoms = $dom->find('.video_iframe');
        $sprindboard = 'http://read.html5.qq.com/image?src=forum&q=4&r=0&imgflag=7&imageUrl=';
		foreach ($imageDoms as $imageDom) {
			$dataSrc = $imageDom->getAttribute('data-src');
			if (!$dataSrc) {
				continue;
			}
			$src  = $sprindboard . $dataSrc;
			$imageDom->setAttribute('src', $src);
		}
		foreach ($videoDoms as $videoDom) {
			$dataSrc = $videoDom->getAttribute('data-src');
			// 视频不用跳板
			$videoDom->setAttribute('src', $dataSrc);
		}
		// 下载图片到本地
		return ws_downloadImage($postId, $dom);
}
function ws_insert_by_urls($urls) {
    if ( is_admin() ) {
        require_once(ABSPATH . 'wp-admin/includes/admin.php');
    }
	global $wpdb;

	// 微信原作者
	$changeAuthor   = false;
	// 改变发布时间
	$changePostTime = isset($_REQUEST['change_post_time']) && $_REQUEST['change_post_time'] == 'true';
	// 默认是直接发布
	$postStatus     = isset($_REQUEST['post_status']) && in_array($_REQUEST['post_status'], array('publish', 'pending', 'draft')) ?
						$_REQUEST['post_status'] : 'publish';
	// 保留文章样式
	$keepStyle      = isset($_REQUEST['keep_style']) && $_REQUEST['keep_style'] == 'keep';
	// 文章分类，默认是未分类（1）
	$postCate       = isset($_REQUEST['post_cate']) ? intval($_REQUEST['post_cate']) : 1;
	$postCate       = array($postCate);
	// 文章类型，默认是post
	$postType       = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : 'post';

	
	$urls           = str_replace('https', 'http', $urls);
    global $file;
    $config = array(
		'changeAuthor'    => $changeAuthor,
		'changePostTime'  => $changePostTime,
		'postStatus'   => $postStatus,
        'postType' => $postType,
		'keepStyle'     => $keepStyle,
		'postCate' => $postCate,
        'downloadImage' => true
	);
	foreach ($urls as $url) {
        ws_insert_by_url($url, $config);
	}
	$GLOBALS['done'] = true;
	return $postId;
}
function ws_downloadImage($postId, $dom, $keepSource = true) {
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
            return $return_array;    
        }
        else { // amend the url
			$imageInfo = wp_get_attachment_image_src($id, 'full');
			$src       = $imageInfo[0];
			$image->setAttribute('src', $src);
		}
	}
	$userName = $dom->find('#profileBt a', 0);
     if($userName){
      $userName = $userName->plaintext;
     }
     else{ // handle 转载
         $userName = $dom->find('.original_account_nickname', 0)->plaintext;
     }
	$userName = esc_html($userName);    

    // clean up the javascript
	$content = $dom->find('#js_content', 0)->innertext;
	$content = preg_replace('/data\-([a-zA-Z0-9\-])+\=\"[^\"]*\"/', '', $content);
	$content = preg_replace('/src=\"(http:\/\/read\.html5\.qq\.com)([^\"])*\"/', '', $content);
	$content = preg_replace('/class=\"([^\"])*\"/', '', $content);
	$content = preg_replace('/id=\"([^\"])*\"/', '', $content);
    // 保留来源
	if ($keepSource) {
		$source =
				"<blockquote class='keep-source'>" .
				"<p>始发于微信公众号：{$userName}</p>" .
				"</blockquote>";
		$content .= $source;
	}
	// 保留文章样式
	$content = trim($content);
	$return_postID = wp_update_post(array(
		'ID' => $postId,
		'post_content' =>  $content
	));
    if(is_wp_error($return_postID)){
        return array('post_id' => -7, 'err_msg' => $return_postID->get_error_message());
    }
    return array('post_id' => $postId, 'err_msg' => 'create post successfully');
    
}
?>