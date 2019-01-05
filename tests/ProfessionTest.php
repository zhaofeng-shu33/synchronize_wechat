<?php
use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');

require_once "test_utility.php";

class ProfessionTest extends TestCase
{
    private $bg_str = "6, 239);background-image: url(&quot;https://mmbiz.qpic.cn/mmbiz_gif/TYHiaIibSicBzgkLViaglJo8iayjdlvSicWsX5ZMAeGLFyoiccIVy8WZcXbIDNGpupoQ6BMtiblIbFPDvia758JJNibW9oBg/640?wx_fmt=gif&quot;);box-sizing: ";
    private $webpage_url = 'https://mp.weixin.qq.com/s/7HTgGse3B5IT7sIjByBg-A';
    private $post_title = '2018的最后一天，恭喜你入选彩虹桥计划成员';
    private $html_file_name = 'asset/profession.txt';
    private $postId = NULL;
    //! get dom object
    private function get_dom()
    {
        $postId = $this->get_postId();
        $post_obj = get_post($postId);
        $html = fetch_html($this->html_file_name, $this->webpage_url);
        $dom  = str_get_html($html);
        return $dom;
    }
    private function get_postId()
    {
        if($this->postId)
            return $this->postId;
        $this->postId = post_exists($this->post_title);
        if($this->postId == 0){
            $return_array = wsync_insert_by_url($this->webpage_url);
            $this->postId = $return_array['status_code'];
        }
        return $this->postId;
    }
    public function test_wsync_resolve_bg_image()
    {
        $postId = $this->get_postId();
        wsync_resolve_bg_image($this->bg_str, $postId);
        $this->assertTrue(strpos($this->bg_str, '2325a5e7156c635c0f0940b6654991a786891c34.gif&quot;')>0);
    }
    public function test_wsync_resolve_origin(){
        $dom = $this->get_dom();
        $origin = wsync_resolve_origin($dom);
        $this->assertSame($origin, 'THU深研院职协');
    }
}

?>