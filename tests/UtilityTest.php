<?php

use PHPUnit\Framework\TestCase;


define('ABSPATH', getenv("ABSPATH"));
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');
require_once('wechat_synchronize.php');

class UtilityTest extends TestCase
{
    public function test_sync_wechat_split_url(){
        $url_str = "http://wordpress.org/ 123\nhttp://wordpress.org/124";
        $url_list = sync_wechat_split_url($url_str);
        $this->assertSame($url_list[0],"http://wordpress.org/%20123");
        $this->assertSame($url_list[1],"http://wordpress.org/124");
    }
}