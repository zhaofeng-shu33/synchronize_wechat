<?php

use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');
class WxUrlTest extends TestCase
{
    private $webpage_url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';
    private $image_url = 'http://mmbiz.qpic.cn/mmbiz_jpg/kNeT3AGutVYFPzwOfMnjX9coe2CdyZoMwHscMdH9VZHlbiblibgUVRsGqIjmM65jGgbniaA0ibfaSjKhUm6Jehia3gQ/0?wx_fmt=jpeg';
    private $image_name = 'mzxl_thu.jpeg';
    private $checksum_image_url = 'http://res.wx.qq.com/mmbizwap/zh_CN/htmledition/images/pic/appmsg/pic_reward_qrcode.2x3534dd.png';
    private function get_html()
    {
        $html_file_name = 'teach.txt';
        if(file_exists($html_file_name)){
            $html = file_get_contents($html_file_name);
        }
        else{
            //check for right output of function `get_html`
            $html = get_html($this->webpage_url);
            file_put_contents($html_file_name, $html);
        }
        return $html;
    }
    private function get_image()
    {
        return ws_upload_image($this->image_url, 1, $this->image_name);    
    }
    /**
     * @group local
     */
    public function test_ws_insert_by_html_false(){
        $html = self::get_html();
        $html_remove_title = str_replace('msg_title', 'title_msg', $html);
        $return_array = ws_insert_by_html($html_remove_title);
        $this->assertSame($return_array['post_id'], -3);

        $html_change_title = preg_replace('/msg_title = "([^\"]+)"/', 'msg_title = "Hello world!"', $html);
        $return_array = ws_insert_by_html($html_change_title);
        $this->assertSame($return_array['err_msg'], 'the article is already in the database');
    }
    /**
     * @group local
     */
    public function test_ws_insert_by_html_true(){
        $html = self::get_html();
        $return_array = ws_insert_by_html($html);
        $pid = $return_array['post_id'];
        $this->assertTrue($pid > 0);
        // no state should be traced
        wp_delete_post($pid, true);    
    }
    /**
     * @group local
     */
    public function test_check_wx_url()
    {
        $this->assertSame(check_wx_url($this->webpage_url), $this->webpage_url);
        $this->assertSame(check_wx_url('http://baidu.com'),"");
    }

    /**
     * @group network
     */
    public function test_ws_insert_by_url_false()
    {
        $return_array = ws_insert_by_url('http://baidu.com');        
        $this->assertSame($return_array['post_id'], -1);

        $return_array = ws_insert_by_url(str_replace('Yu','uY',$this->webpage_url)); 
        $this->assertSame($return_array['post_id'], -3);
    }

    /**
     * @group network
     */
    public function test_ws_upload_image_false()
    {
        $image_url_false = str_replace('kNe', 'eNk', $this->image_url);

        $return_array = ws_upload_image($image_url_false, 1);
        $this->assertSame($return_array['post_id'], -5);
    }
    /**
     * @group local
     */
    public function test_ws_upload_image_true()
    {
        $return_array = self::get_image();
        $this->assertTrue($return_array['post_id'] > 0);
    }
    public function test_ws_upload_image_checksum()
    {
        $return_array = ws_upload_image($this->checksum_image_url, 1);
        $this->assertTrue($return_array['post_id'] > 0);
        $return_array = ws_upload_image($this->checksum_image_url, 1);
        $this->assertSame($return_array['err_msg'], 'image already exists');

    }
    public function test_ws_get_image_name()
    {
        // use this file for testing
        $image_name = _get_image_name(__DIR__ . '/asset/' . 'sha1.png', '');
        $this->assertSame($image_name,'1c45882237028e5792b7add3307ef18631119645.png');
    }
    /**
     * @group local
     */
    public function test_ws_check_image_exists()
    {
        self::get_image();
        $return_array = ws_check_image_exists(1, $this->image_name);
        $this->assertSame($return_array['err_msg'], 'image already exists');

        $return_array = ws_check_image_exists(1, 'Picture2.png');
        $this->assertSame($return_array['post_id'], 0);
    }
    
    public function test_ws_set_feature_image()
    {
        $return_array = ws_set_feature_image(1, $this->image_url, $this->image_name);
        $this->assertTrue($return_array['post_id'] > 0);
    }
    public function test_ws_downloadImage()
    {
        $html = $this->get_html();
        $return_array = ws_insert_by_html($html);
        $this->assertTrue($return_array['post_id'] > 0);

        $postId = $return_array['post_id'];
        $return_array = ws_set_image($html, $return_array['post_id']);
        $this->assertTrue($return_array['post_id'] > 0);
    }
}
?>