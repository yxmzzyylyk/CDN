<?php

/**
 * 熊掌ID推送
 * Class WB_BSL_App
 */

class WB_BSL_App
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
        $row = $wpdb->get_row("SELECT id,remain,limited FROM $t WHERE ymd='$ymd' AND `type`=1");
        if($row && $row->limited > 0){
            $remain = intval($row->remain);
        }

        return $remain;
    }

    public static function update_remain($num){

        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';

        $num = intval($num);

        $ymd = current_time('Y-m-d');
        $row = $wpdb->get_row("SELECT * FROM $t WHERE ymd='$ymd' and `type`=1 ");
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
                'type'=>1,
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

            $app_active = WB_BSL_Conf::cnf('app_active');
            if(!$app_active){
                break;
            }
            $token = WB_BSL_Conf::cnf('app_token');
            $appid = WB_BSL_Conf::cnf('app_id');
            if(!$token || !$appid){
                break;
            }

            if(WB_BSL_Conf::cnf('daily_active')){
                break;
            }

            if(!WB_BSL_Conf::check_post_type($post)){
                return;
            }

            $remain = self::get_remain();

            $type  = 3;
            $api_type = 'batch';
            if($remain > 0){
                $type = 2;
                $api_type = 'realtime';
            }else{
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

            WB_BSL_Utils::txt_log(array('app_push',$api_type,$remain,$type));
            WB_BSL_Utils::run_log('天级收录，推送url：','收录推送');
            WB_BSL_Utils::run_log($post_url,'收录推送');
            $ret = WB_BSL_Baidu::app_push($url,$api_type);
            WB_BSL_Utils::run_log('推送结果【'.$ret['desc'].'】','收录推送');

            WB_BSL_Utils::add_push_log($type,$post_id,$post_url,$ret);

            if($type == 2 && !$ret['code'] && isset($ret['data']) && isset($ret['data']['remain'])){
                self::update_remain($ret['data']['remain']);
            }


        }while(false);
    }

    public static function upgrade_v3_data(){

        global $wpdb;

        if(!WB_BSL_Conf::check_tb_exists()){
            return;
        }


        $t = $wpdb->prefix.'wb_bsl_log';
        $post_url = home_url('/?p=');

        $sql = "INSERT INTO `$t` (`post_id`,`post_url`,`create_date`,`type`,`push_status`)";
        $sql .= "SELECT post_id,CONCAT_WS('','$post_url',post_id) AS url,CONCAT_WS('-',SUBSTR(meta_value,3,4),SUBSTR(meta_value,7,2),SUBSTR(meta_value,9,2)) AS create_date,";
        $sql .= "IF(SUBSTR(meta_value,1,1)=1,2,3) AS `type`,1 AS push_status ";
        $sql .= "FROM `$wpdb->postmeta` WHERE `meta_key` = 'bd_bf' ORDER BY meta_id ASC";

        $wpdb->query($sql);
        $wpdb->query("UPDATE $t a,$wpdb->postmeta b SET a.index_status=1 WHERE a.post_id=b.post_id AND a.index_status=0 AND b.meta_key='url_in_baidu' AND b.meta_value='1'");
        $wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key='bd_bf'");





    }

}