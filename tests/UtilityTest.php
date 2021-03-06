<?php

use PHPUnit\Framework\TestCase;

if (! defined('ABSPATH')) {
define('ABSPATH', getenv("ABSPATH"));
}
require_once(ABSPATH . 'wp-config.php');
require_once(ABSPATH . 'wp-admin/includes/admin.php');

require_once('synchronize_api.php');

class UtilityTest extends TestCase
{
    public function test_sync_wechat_split_url(){
        $url_str = "http://wordpress.org/123\nhttp://wordpress.org/124";
        $url_list = sync_wechat_split_url($url_str);
        $this->assertSame($url_list[0],"http://wordpress.org/123");
        $this->assertSame($url_list[1],"http://wordpress.org/124");
    }
    public function test_sync_wechat_get_latest_post_publish_date(){
        $compare_date = strtotime('2018-08-04 00:00:00');
        $latest_date = sync_wechat_get_latest_post_publish_date();
        $this->assertTrue($latest_date > $compare_date);
    }
}