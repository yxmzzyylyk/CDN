<?php
/*
Plugin Name: 搜索推送管理插件
Plugin URI: http://wordpress.org/plugins/baidu-submit-link/
Description: 搜索推送管理插件（原百度搜索推送管理插件）是一款针对WP开发的功能非常强大的百度、Bing和360搜索引擎链接推送插件。协助站长将网站资源快速推送至百度、Bing和360搜索引擎，有利于提升网站的搜索引擎收录效率；该插件还提供文章百度收录查询功能。
Author: wbolt team
Version: 3.4.13
Author URI: https://www.wbolt.com/
Requires PHP: 5.4.0
*/

if(!defined('ABSPATH')){
    return;
}

define('BSL_PATH',dirname(__FILE__));
define('BSL_BASE_FILE',__FILE__);
define('BSL_VERSION','3.4.13');

require_once BSL_PATH.'/classes/conf.class.php';
require_once BSL_PATH.'/classes/baidu.class.php';
require_once BSL_PATH.'/classes/utils.class.php';
require_once BSL_PATH.'/classes/cron.class.php';
require_once BSL_PATH.'/classes/site.class.php';
require_once BSL_PATH.'/classes/app.class.php';
require_once BSL_PATH.'/classes/daily.class.php';
require_once BSL_PATH.'/classes/bing.class.php';
require_once BSL_PATH.'/classes/stats.class.php';
require_once BSL_PATH.'/classes/admin.class.php';

//new BSL_Admin();
BSL_Admin::init();
