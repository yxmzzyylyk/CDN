<?php
defined( 'ABSPATH' ) || exit;

// 匹配出css、js、图片地址
if ( ! function_exists( 'izt_replace_url' ) ) :
    function izt_replace_url($str){
        $regexp = "/<(link|script|img)([^<>]+)>/i";
        $str = preg_replace_callback( $regexp, "izt_replace_callback", $str );
        return $str;
    }
endif;

// 匹配需要替换掉的链接地址
if ( ! function_exists( 'izt_replace_callback' ) ) :
    function izt_replace_callback($matches) {
        global $options;
        $google = isset($options["wafc_google"]) ? $options["wafc_google"] : 0;
        $gravatar = isset($options["wafc_gravatar"]) ? $options["wafc_gravatar"] : 0;
        $google = !$google?1:$google;
        $gravatar = !$gravatar?1:$gravatar;
        $google_array = array('.geekzu.org', '.geekzu.org', '.geekzu.org');
        $gravatar_array = array('https://secure.gravatar.com/avatar', '//cn.gravatar.com/avatar', '//cdn.v2ex.com/gravatar', '//fdn.geekzu.org/avatar');
        $str = $matches[0];
        $patterns = array();
        $replacements = array();
        if($google>0){
            // 匹配谷歌CDN链接
            $patterns[0] = '/\.googleapis\.com/';
            // 使用CDN地址替换
            $replacements[0] = $google_array[$google-1];

            if($google=='2'){
                $patterns[2] = '/(http|https):\/\/fonts\./';
                $replacements[2] = '//fonts.';
                $patterns[3] = '/(http|https):\/\/ajax\./';
                $replacements[3] = '//ajax.';
            }
        }
        if($gravatar>0){
            // 匹配头像链接
            if($gravatar=='1'){
                $patterns[1] = '/(http|https):\/\/[0-9a-zA-Z]+\.gravatar\.com\/avatar/';
            }else{
                $patterns[1] = '/\/\/[0-9a-zA-Z]+\.gravatar\.com\/avatar/';
            }
            // 使用可以访问到头像图片替换
            $replacements[1] = $gravatar_array[$gravatar-1];
        }
        return preg_replace($patterns, $replacements, $str);
    }
endif;

if ( ! function_exists( 'izt_replace_start' ) ) :
    function izt_replace_start() {
        //开启缓冲
        ob_start("izt_replace_url");
    }
    add_action('wp_loaded', 'izt_replace_start');
endif;

if ( ! function_exists( 'izt_replace_end' ) ) :
    function izt_replace_end() {
        // 关闭缓冲
        if(ob_get_level() > 0) ob_end_flush();
    }
    add_action('shutdown', 'izt_replace_end');
endif;

add_filter('pre_http_request', 'wpcom_pre_http_request', 20, 3);
function wpcom_pre_http_request($pre, $parsed_args, $url){
    global $options;
    if( class_exists('WP_CHINA_YES') ) return $pre;
    if ( ! stristr($url, 'api.wordpress.org') && ! stristr($url, 'downloads.wordpress.org')) return $pre;
    if( (isset($options['wp-proxy']) && $options['wp-proxy']) || !isset($options['wp-proxy'])){
        $url = str_replace('api.wordpress.org', 'api.w.org.ibadboy.net', $url);
        $url = str_replace('downloads.wordpress.org', 'd.w.org.ibadboy.net', $url);
        $pre = wp_remote_request($url, $parsed_args);
    }
    return $pre;
}