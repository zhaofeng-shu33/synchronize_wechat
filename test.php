<?php

use PHPUnit\Framework\TestCase;
define('ABSPATH', 'C:/Users/H/Documents/tech/php/wordpress-4.9.8/');
require_once(ABSPATH . 'wp-config.php');
require_once('insert_by_url.php');

class WxUrlTest extends TestCase
{
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
    public function test_get_html()
    {
        $html_file_name = 'teach.txt';
        if(file_exists($html_file_name)){
            $html = file_get_contents($html_file_name);
        }
        else{
            //check for right output of function `get_html`
            $html = get_html($url);
            file_put_contents($html_file_name, $html);
        }
        $url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';        
        $html_tmp = get_html(str_replace('Yu','uY',$url));
        $this->assertSame($html_tmp, "");
    }    
}