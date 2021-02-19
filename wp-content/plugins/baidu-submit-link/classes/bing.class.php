<?php


/**
 * bing 推送
 * Class WB_BSL_Bing
 */


class WB_BSL_Bing
{
    public static function init(){

        add_action('edit_post',array(__CLASS__,'bsl_edit_post'),91,2);
        add_action('wp_insert_post',array(__CLASS__,'bsl_edit_post'),91,2);

    }


    public static function push_batch_url($urls)
    {
        $ret = array('code'=>1,'desc'=>'fail');
        $key = WB_BSL_Conf::cnf('bing_key');
        if(!$key){
            $ret['desc'] = '未设置API密钥';
            return $ret;
        }
        $urls = trim($urls);
        $urls = str_replace("\r\n","\n",$urls);
        $urls = explode("\n",$urls);
        $submit_url = array();
        $home_url = home_url();
        $len = strlen($home_url);
        foreach($urls as $url){
            $url = trim($url);
            if(empty($url)){
                continue;
            }
            if(substr($url,0,$len)!=$home_url){
                continue;
            }
            $submit_url[] = $url;
        }
        if(empty($submit_url)){

            $ret['desc'] = '有效链接为空';
            return $ret;
        }

        $api = 'https://www.bing.com/webmaster/api.svc/json/SubmitUrlbatch?apikey='.$key;
        $http = wp_remote_post($api,array('sslverify'=>false,'headers'=>array('Content-Type'=>'text/json; charset=utf-8'),'body'=>json_encode(array('siteUrl'=>home_url(),'urlList'=>$submit_url))));
        if(is_wp_error($http)){
            $ret['desc'] = "提交出错".$http->get_error_message();
            return $ret;
        }
        $body = wp_remote_retrieve_body($http);
        if(!$body){
            $ret['desc'] = 'API响应为空';
            return $ret;
        }
        $data = json_decode($body,'true');

        if(!$data){
            $ret['desc'] = 'API响应解析出错';
            return $ret;
        }
        if(isset($data['ErrorCode'])){
            $ret['code'] = $data['ErrorCode'];
            $ret['desc'] = 'URL提交出错，'.$data['Message'];

            self::add_batch_log($submit_url,0);
            return $ret;
        }
        $ret['code'] = 0;
        $ret['desc'] = 'success';
        $ret['data'] = array('success'=>1,'d'=>$data,'urls'=>$submit_url);

        self::add_batch_log($submit_url,1);
        return $ret;

    }

    public static function url_to_post_id($url){

        global $wp_rewrite;

        $part_url = str_replace(home_url('/'),'',$url);
        if(preg_match('#\?p=(\d+)#',$url,$match)){
            return $match[1];
        }
        return url_to_postid($url);
    }

    public static function add_batch_log($urls,$status)
    {


        global $wpdb;
        $t = $wpdb->prefix . 'wb_bsl_log';

        //url_to_postid()

        foreach ($urls as $url) {
            $d = array(
                'post_id' => self::url_to_post_id($url),
                'post_url' => $url,
                'push_status' => $status,
                'index_status' => 0,
                'create_date' => current_time('mysql'),
                'type' => 11,
                'result' => '',
            );
            if($wpdb->insert($t, $d)){
                $d['id'] = $wpdb->insert_id;
            }
            do_action('wb_bsl_add_push_log',$d);
        }
    }

    public static function summary()
    {
        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';


        $now = current_time('Y-m-d');

        $row = $wpdb->get_row("SELECT * FROM $t WHERE ymd='$now' AND `type`=2");
        $summary = array(
            'quota'=>array('name'=>'剩余配额','value'=>0),
            'success'=>array('name'=>'成功推送','value'=>0),
            'fail'=>array('name'=>'推送失败','value'=>0),
        );

        if($row){
            $summary['quota']['value'] = $row->remain;
        }
        $log = $wpdb->prefix.'wb_bsl_log';
        $row = $wpdb->get_row("SELECT SUM(IF(push_status=1,1,0)) AS succ_num,COUNT(1) num FROM $log WHERE `type` IN(10,11) AND DATE_FORMAT(create_date,'%Y-%m-%d') ='$now'");


        if($row){
            $row->succ_num = $row->succ_num?$row->succ_num:0;
            $summary['success']['value'] = $row->succ_num;
            $summary['fail']['value'] = $row->num - $row->succ_num;
        }

        return array_values($summary);


    }
    public static function push_url($url)
    {
        $ret = array('code'=>1,'desc'=>'fail');


        $key = WB_BSL_Conf::cnf('bing_key');
        if(!$key){
            $ret['desc'] = 'empty api key';
            return $ret;
        }

        $api = 'https://www.bing.com/webmaster/api.svc/json/SubmitUrl?apikey='.$key;
        $http = wp_remote_post($api,array('sslverify'=>false,'headers'=>array('Content-Type'=>'text/json; charset=utf-8'),'body'=>json_encode(array('siteUrl'=>home_url(),'url'=>$url))));
        if(is_wp_error($http)){
            $ret['desc'] = $http->get_error_message();
            return $ret;
        }

        $body = wp_remote_retrieve_body($http);
        if(!$body){
            $ret['desc'] = 'empty response';
            return $ret;
        }
        $data = json_decode($body,'true');

        if(!$data){
            $ret['desc'] = 'response decode error';
            return $ret;
        }
        if(isset($data['ErrorCode'])){
            $ret['code'] = $data['ErrorCode'];
            $ret['desc'] = $data['Message'];
            return $ret;
        }
        $ret['code'] = 0;
        $ret['desc'] = 'success';
        $ret['data'] = array('success'=>1,'d'=>$data);
        return $ret;

    }

    public static function update_quoter($init=0)
    {
        global $wpdb;
        $t = $wpdb->prefix.'wb_bsl_day';

        $quota = self::get_quota();
        if(!$quota){
            return;
        }

        $daily_quota = $quota['DailyQuota'];

        $ymd = current_time('Y-m-d');
        $row = $wpdb->get_row("SELECT * FROM $t WHERE ymd='$ymd' and `type`=2 ");
        if($row){
            $wpdb->query($wpdb->prepare("UPDATE $t SET remain=%d WHERE id=%d",$daily_quota,$row->id));
        }else{

            $d = array(
                'ymd'=>$ymd,
                'limited'=>($init?$daily_quota:$daily_quota+1),
                'remain' => $daily_quota,
                'type'=>2,
            );
            $wpdb->insert($t,$d);
        }

    }

    public static function get_quota(&$ret = array())
    {
        $key = WB_BSL_Conf::cnf('bing_key');
        if(!$key){
            if(is_array($ret)){
                $ret['desc'] = 'key未设置';
            }
            return 0;
        }
        $api = 'https://www.bing.com/webmaster/api.svc/json/GetUrlSubmissionQuota?siteUrl=%s&apikey=%s';
        $api = sprintf($api,home_url(),$key);
        $http = wp_remote_get($api,array('sslverify'=>false));
        if(is_wp_error($http)){
            if(is_array($ret)){
                $ret['desc'] = 'error['.$http->get_error_message().']';
            }
            return 0;
        }


        $body = wp_remote_retrieve_body($http);
        if(!$body){
            if(is_array($ret)){
                $ret['desc'] = 'empty body';
            }
            return 0;
        }

        $data =json_decode($body,true);

        if(!$data){
            if(is_array($ret)){
                $ret['desc'] = 'error decode body['.$body.']';
            }
            return 0;
        }

        if(isset($data['ErrorCode'])){
            if(is_array($ret)){
                $ret['code'] = $data['ErrorCode'];
                $ret['desc'] = $data['Message'];
            }
            return 0;
        }else if(isset($data['d'])){
            if(is_array($ret)){
                $ret['code'] = 0;
                $ret['desc'] = 'success';
                $ret['data'] = $data['d'];
            }
            return $data['d'];
        }
        if(is_array($ret)){
            $ret['desc'] = 'undefined data';
        }
        //'{"DailyQuota":10,"MonthlyQuota":140}'
        return 0;
    }


    public static function api_req()
    {

        //{"d":null}
        //{"ErrorCode":14,"Message":"ERROR!!! NotAuthorized"}

        //{"ErrorCode":2,"Message":"ERROR!!! You have exceeded your daily url submission quota : 10"}
    }

    public static function bsl_edit_post($post_id,$post){

        if(!get_option('wb_bsl_ver',0)){
            return;
        }

        static $post_ids = array();
        do{
            if(isset($post_ids[$post_id]))return;
            $post_ids[$post_id] = 1;
            $bing_auto = WB_BSL_Conf::cnf('bing_auto');
            if(!$bing_auto){
                break;
            }
            $bing_key = WB_BSL_Conf::cnf('bing_key');
            if(!$bing_key){
                break;
            }

            if(!WB_BSL_Conf::check_post_type($post)){
                return;
            }

            $msg = array();
            $quota = self::get_quota($msg);
            if(!$quota){
                WB_BSL_Utils::run_log('Bing推送获取配额失败['.$msg['desc'].']','收录推送');
                break;
            }
            //DailyQuota,MonthlyQuota
            $remain = $quota['DailyQuota'];
            if($remain<1){
                break;
            }

            $type = 10;//10=>bing_auto,11=>bing_manual

            $log = WB_BSL_Utils::push_log($post_id,$type);
            /*if($log && $log->push_status == 1){
                break;
            }*/

            if($log && current_time('timestamp') - strtotime($log->create_date) < 300){
                break;
            }


            $post_url = get_permalink($post);

            WB_BSL_Utils::run_log('Bing推送，推送url：','收录推送');
            WB_BSL_Utils::run_log($post_url,'收录推送');
            $ret = self::push_url($post_url);
            WB_BSL_Utils::run_log('推送结果【'.$ret['desc'].'】','收录推送');

            WB_BSL_Utils::add_push_log($type,$post_id,$post_url,$ret);

            self::update_quoter();

        }while(false);
    }

}