<?php

use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');

require_once "test_utility.php";


class SummaryTest extends TestCase
{
    private $webpage_url = 'https://mp.weixin.qq.com/s/YZnJhFKKbZBxvpRCGclWSg';
    private $html_file_name = 'asset/summary.txt';
    private $postId = Null;
    private $post_title = '志愿者部年度总结';
  
    private function get_postId()
    {
        if($this->postId)
            return $this->postId;
        $this->postId = post_exists($this->post_title);
        if($this->postId == 0){
            $return_array = wsync_insert_by_url($this->webpage_url);
            $this->postId = $return_array['post_id'];
        }
        return $this->postId;
    }    

}
?>