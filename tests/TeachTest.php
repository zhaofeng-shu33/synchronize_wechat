<?php

use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');

require_once "test_utility.php";


class TeachTest extends TestCase
{
    private $webpage_url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';
    private $publish_date = '2018-08-03';
    private $image_url = 'http://mmbiz.qpic.cn/mmbiz_jpg/kNeT3AGutVYFPzwOfMnjX9coe2CdyZoMwHscMdH9VZHlbiblibgUVRsGqIjmM65jGgbniaA0ibfaSjKhUm6Jehia3gQ/0?wx_fmt=jpeg';
    private $image_name = 'mzxl_thu.jpeg';
    private $checksum_image_url = 'http://res.wx.qq.com/mmbizwap/zh_CN/htmledition/images/pic/appmsg/pic_reward_qrcode.2x3534dd.png';
    private $html_file_name = 'asset/teach.txt';
    private function get_image()
    {
        return ws_upload_image($this->image_url, 1, $this->image_name);    
    }
    public function test_ws_publish_date(){
        $html = fetch_html($this->html_file_name, $this->webpage_url);
        $pd = get_publish_date($html);
        $this->assertTrue(strstr($pd, $this->publish_date)!=Null);
    }
    /**
     * @group local
     */
    public function test_ws_insert_by_html_false(){
        $html = fetch_html($this->html_file_name, $this->webpage_url);
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
        $html = fetch_html($this->html_file_name, $this->webpage_url);
        $return_array = ws_insert_by_html($html, array('postStatus' => 'publish'));
        $pid = $return_array['post_id'];
        $this->assertTrue($pid > 0);
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
    public function test_ws_insert_by_url_true()
    {
        $return_array = ws_insert_by_url($this->webpage_url);   
        $this->assertTrue($return_array['post_id'] > 0);
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
        $image_name = _get_image_name($this->image_url, '');
        $this->assertSame($image_name,'85463a5165d4e4bb33579bff7cf005b162ec8ac0.jpeg');
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
        $html = fetch_html($this->html_file_name, $this->webpage_url);
        $return_array = ws_insert_by_html($html);
        $this->assertTrue($return_array['post_id'] > 0);

        $postId = $return_array['post_id'];
        $return_array = ws_set_image($html, $return_array['post_id']);
        $this->assertTrue($return_array['post_id'] > 0);
    }
}
?>