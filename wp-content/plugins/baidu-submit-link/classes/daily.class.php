<?php

/**
 * 快速推送
 * Class WB_BSL_Daily
 */

class WB_BSL_Daily
{


    public static function init(){



        add_action('edit_post',array(__CLASS__,'bsl_edit_post'),91,2);
        add_action('wp_insert_post',array(__CLASS__,'bsl_edit_post'),91,2);

    }


    public static function get_remain(){
        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';
        $remain = 10;
        $ymd = current_time('Y-m-d');
        $row = $wpdb->get_row("SELECT id,remain,limited FROM $t  WHERE ymd='$ymd' AND `type`=1");
        if($row ){
            $remain = intval($row->remain);
        }

        return $remain;
    }

    public static function update_remain($num){

        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';

        $num = intval($num);

        $ymd = current_time('Y-m-d');
        $row = $wpdb->get_row("SELECT * FROM $t WHERE ymd='$ymd' AND `type`=1 ");
        if($row){
            if($row->limited>0){
                $wpdb->query($wpdb->prepare("UPDATE $t SET remain=%d WHERE id=%d",$num,$row->id));
            }else{
                $wpdb->query($wpdb->prepare("UPDATE $t SET remain=%d,limited=%d WHERE id=%d",$num,$num+1,$row->id));
            }
        }else{
            $d = array(
                'ymd'=>$ymd,
                'limited'=>$num + 1,
                'remain' => $num,
                'type'=>1
            );
            $wpdb->insert($t,$d);
        }
    }

    public static function bsl_edit_post($post_id,$post){

        if(!get_option('wb_bsl_ver',0)){
            return;
        }

        $disable_push = get_post_meta($post_id,'wb_bsl_daily_push',true);
        if($disable_push){
            return;
        }

        static $post_ids = array();
        do{
            if(isset($post_ids[$post_id]))return;
            $post_ids[$post_id] = 1;

            $app_active = WB_BSL_Conf::cnf('daily_active');
            if(!$app_active){
                break;
            }
            $api = WB_BSL_Conf::cnf('daily_api');

            if(!$api){
                break;
            }

            if(!WB_BSL_Conf::check_post_type($post)){
                return;
            }

            $remain = self::get_remain();

            $type  = 2;
            if(!$remain || $remain <1){
                break;
            }

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
            WB_BSL_Utils::run_log('快速收录，推送url：','收录推送');
            WB_BSL_Utils::run_log($post_url,'收录推送');
            $ret = WB_BSL_Baidu::daily_push($url);
            WB_BSL_Utils::run_log('推送结果【'.$ret['desc'].'】','收录推送');

            WB_BSL_Utils::add_push_log($type,$post_id,$post_url,$ret);

            if(!$ret['code'] && isset($ret['data']) && isset($ret['data']['remain_daily'])){
                self::update_remain($ret['data']['remain_daily']);
            }


        }while(false);
    }

}