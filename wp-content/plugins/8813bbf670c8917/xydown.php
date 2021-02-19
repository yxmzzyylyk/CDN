<?php
/*
Plugin Name: xydown独立下载页面
Plugin URI:www.zmki.cn
Description: 实现wordpress独立下载页面的一款插件
Author:钻芒博客
*/
/*本插件由模板兔早期开发，钻芒博客后期作了补充调整。
 * 优化了下载页面及信息框UI。新增了蓝奏云、360网盘密码等功能。
 * 2019年5月31日12:37:23
*/
global $wpdb;
define("xydown", plugin_dir_path(__FILE__));
function xydown_style() {
    //	echo'<link rel="stylesheet" href="'.plugin_dir_url( __FILE__ ).'css/style.css" type="text/css" />';
    echo '<link href="https://cdn.jsdelivr.net/gh/yxmzzyylyk/emlog@4.1/dow2.css"	  type="text/css" rel="stylesheet" />';
}
add_action('wp_head', 'xydown_style');
function xydown_show_down($content) {
    if (is_single()) {
        $xydown_start = get_post_meta(get_the_ID() , 'xydown_start', true);
        $xydown_name = get_post_meta(get_the_ID() , 'xydown_name', true);
        $xydown_size = get_post_meta(get_the_ID() , 'xydown_size', true);
        $xydown_date = get_post_meta(get_the_ID() , 'xydown_date', true);
        $xydown_version = get_post_meta(get_the_ID() , 'xydown_version', true);
        $xydown_author = get_post_meta(get_the_ID() , 'xydown_author', true);
        $xydown_downurl1 = get_post_meta(get_the_ID() , 'xydown_downurl1', true);
        $xydown_downurl2 = get_post_meta(get_the_ID() , 'xydown_downurl2', true);
        $xydown_downurl3 = get_post_meta(get_the_ID() , 'xydown_downurl3', true);
        $xydown_yanshi = get_post_meta(get_the_ID() , 'xydown_yanshi', true);
        ////资源名称、资源大小、更新时间、适用版本、作者信息
        if ($xydown_yanshi) {
            $yanshi_content.= '<strong><a class="yanshibtn" rel="external nofollow"   href="' . site_url() . '/demo.php?id=' . get_the_ID() . '" target="_blank" title="' . $xydown_name . ' ">查看演示</a></strong>';
        }
        if ($xydown_start) {
            $content.= '
<div class="sg-dl"><span class="sg-dl-span"><a href="' . site_url() . '/download.php?id=' . get_the_ID() . '" target="_blank"  title="' . $xydown_name . '下载地址"  rel="nofollow noopener noreferrer"><button type="button" class="btn-download">下载地址点这里</button></a></span></div>
				</strong> ' . $yanshi_content . '</p><p></p> ';
        }
    }
    return $content;
}
add_action('the_content', 'xydown_show_down');
?>
<?php
include ('meta-box.php'); ?>
