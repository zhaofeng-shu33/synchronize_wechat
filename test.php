<?php

use PHPUnit\Framework\TestCase;

define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('insert_by_url.php');
class WxUrlTest extends TestCase
{
    private function get_html()
    {
        $html_file_name = 'teach.txt';
        if(file_exists($html_file_name)){
            $html = file_get_contents($html_file_name);
        }
        else{
            //check for right output of function `get_html`
            $url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';
            $html = get_html($url);
            file_put_contents($html_file_name, $html);
        }
        return $html;
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
        $this->assertSame($return_array['post_id'], -4);
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
        $url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';
        $this->assertSame(check_wx_url($url), $url);
        $this->assertSame(check_wx_url('http://baidu.com'),"");
    }

    /**
     * @group network
     */
    public function test_ws_insert_by_url_false()
    {
        $return_array = ws_insert_by_url('http://baidu.com');        
        $this->assertSame($return_array['post_id'], -1);

        $url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';

        $return_array = ws_insert_by_url(str_replace('Yu','uY',$url)); 
        $this->assertSame($return_array['post_id'], -2);
    }

    /**
     * @group network
     */
    public function test_ws_set_feature_image_false()
    {
        $msg_cdn_url = "http://mmbiz.qpic.cn/mmbiz_jpg/kNeT3AGutVYFPzwOfMnjX9coe2CdyZoMwHscMdH9VZHlbiblibgUVRsGqIjmM65jGgbniaA0ibfaSjKhUm6Jehia3gQ/0?wx_fmt=jpeg";
        $msg_cdn_url_false = str_replace('kNe', 'eNk', $msg_cdn_url);

        $return_array = ws_set_feature_image($msg_cdn_url_false, 1);
        $this->assertSame($return_array['post_id'], -5);

        $return_array = ws_set_feature_image($msg_cdn_url, 1, 'mzxl_thu.jpeg');
        $this->assertTrue($return_array['post_id'] > 0);
    }
    /**
     * @group local
     */
    public function test_ws_set_feature_image_true()
    {
        $msg_cdn_url = "http://mmbiz.qpic.cn/mmbiz_jpg/kNeT3AGutVYFPzwOfMnjX9coe2CdyZoMwHscMdH9VZHlbiblibgUVRsGqIjmM65jGgbniaA0ibfaSjKhUm6Jehia3gQ/0?wx_fmt=jpeg";

        $return_array = ws_set_feature_image($msg_cdn_url, 1, 'mzxl_thu.jpeg');
        $this->assertTrue($return_array['post_id'] > 0);
    }

    /**
     * @group local
     */
    public function test_ws_check_image_exists()
    {
        // setup the database should be done here
        $return_array = ws_check_image_exists(1, 'Picture1.png');
        $this->assertSame($return_array['post_id'], 9);

        $return_array = ws_check_image_exists(1, 'Picture2.png');
        $this->assertSame($return_array['post_id'], 0);
    }

    /**
     * @group network
     */
    public function test_get_html()
    {
        $url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';        
        $html_tmp = get_html(str_replace('Yu','uY',$url));
        $this->assertSame($html_tmp, "");
    }    
}
?>