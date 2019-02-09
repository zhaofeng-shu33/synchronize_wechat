<?php

use PHPUnit\Framework\TestCase;

require_once('insert_by_url.php');
if(!class_exists('simple_html_dom_node')){
	require_once("php-simple-html-dom/simple_html_dom.php");
}

class VideoTest extends TestCase
{
   public function test_sync_wechat_process_video()
    {
        $html_str = '<p><iframe class="video_iframe" data-vidtype="2" data-cover="http%3A%2F%2Fvpic.video.qq.com%2F65093407%2Fr1358irzpoh.png" allowfullscreen="" frameborder="0" data-ratio="1.3333333333333333" data-w="648" data-src="https://v.qq.com/iframe/preview.html?width=500&amp;height=375&amp;auto=0&amp;vid=r1358irzpoh"></iframe></p>'
        $dom  = str_get_html($html_str);         
        sync_wechat_process_video($dom);
        $video = $dom->find('#video_iframe')[0];
        $this->assertSame($video->getAttribute('src'), 'https://v.qq.com/iframe/preview.html?width=500&amp;height=375&amp;auto=0&amp;vid=r1358irzpoh');
        $this->assertSame($video->getAttribute('width'), 500);
        $this->assertSame($video->getAttribute('height'), 375);
    }         
}
?>