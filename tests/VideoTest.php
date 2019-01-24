<?php

use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');

require_once "test_utility.php";


class VideoTest extends TestCase
{
    private $webpage_url = 'https://mp.weixin.qq.com/s?__biz=MjM5MDE1MzYzOQ==&mid=503012629&idx=1&sn=4dfdf212a620d461c9aae8f33742bb1f&chksm=3e46d3dd09315acb1d6b5ac56a444afeb6797069ccd91ceb00cb8ecf5d7a487ea1031e6b0744#rd';
    private $html_file_name = 'asset/video.txt';
    private $postId = Null;
    private $post_title = '美在心灵||2018年暑假国际教育公益文化交流会活动简报';
  
    private function get_postId()
    {
        if($this->postId)
            return $this->postId;
        $this->postId = post_exists($this->post_title);
        if($this->postId == 0){
            $return_array = sync_wechat_insert_by_url($this->webpage_url);
            $this->postId = $return_array['post_id'];
        }
        return $this->postId;
    }    
        
}
?>