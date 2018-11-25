<?php
define('ABSPATH', 'C:/Users/H/Documents/tech/php/wordpress-4.9.8/');
require_once(ABSPATH . 'wp-config.php');
require_once('insert_by_url.php');
$url = 'https://mp.weixin.qq.com/s/xGj6-Yu75FWQHc7qtK9AZg';
//check for right output of function `check_wx_url`
echo(check_wx_url($url) . "\n");
//check for wrong output of function `check_wx_url`
if(check_wx_url('http://baidu.com')==''){
    echo('test for check_wx_url passed' . "\n");
}
else{
    die('error occurs when test for check_wx_url');
}
$html_file_name = 'teach.txt';
if(file_exists($html_file_name)){
    $html = file_get_contents($html_file_name);
}
else{
    //check for right output of function `get_html`
    $html = get_html($url);
    file_put_contents($html_file_name, $html);
}
//check for error-handling of function `get_html`
$html_tmp = get_html(str_replace('Yu','uY',$url));
if($html_tmp==''){
    echo('test for get_html passed' . "\n");
}
else{
    die('error occurs when test for get_html');
}

?>