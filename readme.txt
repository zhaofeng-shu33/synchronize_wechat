=== synchronize wechat ===
Contributors: zhaofengshu33
Tags: wechat
Requires at least: 3.0.1
Tested up to: 4.9.9
Requires PHP: 7.1
Stable tag: 0.8
License: Apache License 2.0
License URI: http://www.apache.org/licenses/ 

synchronize wechat post articles to wordpress website
== Description ==
 
This plugin provides a way to synchronize your wordpress website with your wechat account post articles.

Currently we have finished two features:

1. given urls of articles, fetch them and republish them on wordpress website
1. synchronize historical articles to wordpress website by just one click
 
Notice that for the second feature, your account should be authorized by Tencent; *AppId* and *AppSecret* are necessary to fulfill this task. In Chinese:

如果您的微信公众号认证过，请在微信公众平台后台开启开发者模式，获得 AppId 和 AppSecret 填到本拓展的配置页面， 并将wordpress所在的服务器IP地址填到白名单。这样操作后，点击 synchronize 按扭即可同步全部历史文章。

如果您的微信公众号没有认证过，您可以通过在表单上输入推送网址（每行一个）手动添加。

 
== Frequently Asked Questions ==
 
= Can this plugin be used with unauthorized wechat subscription account ? =
 
Yes, but if your account is unauthorized, only manual synchronization can be achieved. You can specify the urls of wechat articles you want to synchronize. 
 
== Changelog ==
= 0.9 =
* Fix wechat upstream changes, double quote of msg_title to single quote

= 0.8 =
* move the plugin position under "edit pages"
* add Chinese translation support

= 0.7.3 =
* remove sync video due to cross-site restriction

= 0.7.2 =
* Add license 

= 0.7 =
* change get post-date reg expression

= 0.6 =
* use jqueryui on frontend modal dialog
* remove debug output at normal state

= 0.5 =
* fix esc url error
* add video support(0.5.2)
* add offset, data_check support(0.5.3)

= 0.4 =
* fix parsing urls error (request from wechat service)

= 0.3 =
* use uniform variable name and function to do the io in client ajax.
* Another change.

= 0.2 =
* set download image timeout

= 0.1 =
* add url cache 
 
== Upgrade Notice ==
 
 

