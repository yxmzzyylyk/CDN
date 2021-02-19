<?php

/**
 * 百度搜索资源推送
 * Class WB_BSL_Site
 */

class WB_BSL_Site
{
    public static function init(){


        add_action('wp_head',array(__CLASS__,'wp_head'),100);
        if(WB_BSL_Conf::cnf('pc_active')){

            add_action('edit_post',array(__CLASS__,'bsl_edit_post'),91,2);
            add_action('wp_insert_post',array(__CLASS__,'bsl_edit_post'),91,2);
        }



    }

    public static function bsl_edit_post($post_id,$post){


        static $post_ids = array();

        //原推送
        do{
            if(isset($post_ids[$post_id]))return;
            $post_ids[$post_id] = 1;

            $pc_active = WB_BSL_Conf::cnf('pc_active');
            if(!$pc_active){
                break;
            }
            $token = WB_BSL_Conf::cnf('token');
            if(!$token){
                break;
            }

            if(!WB_BSL_Conf::check_post_type($post)){
                return;
            }


            $ymd = current_time('Ymd');

            $type = 1;
            $log = WB_BSL_Utils::push_log($post_id,$type);
            if($log && $log->push_status == 1){
                break;
            }

            if($log && current_time('timestamp') - strtotime($log->create_date) < 300){
                break;
            }


            $post_url = get_permalink($post);
            $url = array(
                $post_url,
            );
            WB_BSL_Utils::run_log('普通收录，推送url：','收录推送');
            WB_BSL_Utils::run_log($post_url,'收录推送');
            $ret = WB_BSL_Baidu::pc_push($url,1);
            WB_BSL_Utils::run_log('推送结果【'.$ret['desc'].'】','收录推送');

            WB_BSL_Utils::add_push_log($type,$post_id,$post_url,$ret);


        }while(false);


    }


    public static function wp_head(){

        $is_baidu = 0;
        /*if(WB_BSL_Conf::cnf('bdauto')){
            //百度自动推送JS
            $is_baidu = 1;
        }*/
        $is_360 = 1;
        /*if(WB_BSL_Conf::cnf('qh_active')){
            //360自动推送JS
            $is_360 = 1;
        }*/
        if(!$is_baidu && !$is_360){
            return;
        }

        if(!is_single())return;
        $post = get_post();
        if(!$post){
            return;
        }
        if($post->post_status != 'publish'){
            return;
        }
        if(!WB_BSL_Conf::check_post_type($post)){
            return;
        }

        if($is_baidu){
            wp_enqueue_script('wb-baidu-push',plugin_dir_url(BSL_BASE_FILE).'assets/baidu_push.js',array(),null,true);

            $type = 4;
            $log = WB_BSL_Utils::push_log($post->ID,$type);

            $ymd = current_time('Y-m-d');
            if($log && preg_match('#'.$ymd.'#',$log->create_date)){
                return;
            }
            WB_BSL_Utils::add_push_log($type,$post->ID,get_permalink($post),array('code'=>0,'desc'=>null));
        }
        if($is_360 && $js = WB_BSL_Conf::cnf('qhjs')){
            add_action('wp_footer',array(__CLASS__,'wp_footer'),500);

            $type = 20;
            $log = WB_BSL_Utils::push_log($post->ID,$type);


            $ymd = current_time('Y-m-d');
            if($log && preg_match('#'.$ymd.'#',$log->create_date)){
                return;
            }
            WB_BSL_Utils::add_push_log($type,$post->ID,get_permalink($post),array('code'=>0,'desc'=>null));


        }

    }

    public static function wp_footer(){
        $js =  WB_BSL_Conf::cnf('qhjs');
        $batch = WB_BSL_Conf::cnf('qh_batch');
        if(get_option('wb_bsl_ver',0) && $batch && preg_match('#\.js\?([a-z0-9]+)"#s',$js,$match)){
            echo "<script>var qhcode = '".$match[1]."';</script>";
            echo '<script src="'.plugin_dir_url(BSL_BASE_FILE).'assets/360.js?v=1.1"></script>';
        }else{
            echo $js;
        }

    }
}