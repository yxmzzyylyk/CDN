<?php


/**
 * 插件定时任务
 * Class WB_BSL_Cron
 */
class WB_BSL_Cron
{


    public static function init(){


        add_action('baidu_push_url_cron_action_v3',array(__CLASS__,'baidu_push_url_cron_action_v3'));

        if(!wp_next_scheduled('baidu_push_url_cron_action_v3')){
            wp_schedule_event(strtotime(current_time('Y-m-d H:i:00',1)), 'hourly', 'baidu_push_url_cron_action_v3');
        }
    }


    public static function cron_schedules(){
        return array('five_minute'=>array('interval'=>MINUTE_IN_SECONDS * 5,'display'=>'per five minute'));
    }


    public static function baidu_push_url_cron_action_v3(){



        if(!get_option('wb_bsl_ver',0)){
            return;
        }
        if(!get_option('bsl_version')){
            return;
        }
        set_time_limit(0);

        WB_BSL_Utils::run_log('开始执行百度推送插件定时任务','定时任务');

        self::baidu_index();

        WB_BSL_Bing::update_quoter(1);

        //self::calc_limited();

        self::wb_push();

        self::check_all();

        WB_BSL_Utils::schedule_clean_log();

        WB_BSL_Utils::run_log('百度推送插件定时任务执行完毕','定时任务');


    }

    public static function check_all()
    {

        global $wpdb,$wp_post_types;

        if(!WB_BSL_Conf::cnf('in_bd_active')){
            return;
        }

        $param = get_option('wb_bsl_check_all',array('page'=>0,'ym'=>'202004'));

        if($param['Ym'] != current_time('Ym')){
            return;
        }


        $num = 1000;

        WB_BSL_Utils::run_log('全量文章收录查询','收录查询');

        do{


            $post_types = array();

            $offset = $param['page'] * $num;

            $param['page']++;

            update_option('wb_bsl_check_all',$param,false);



            $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
            if(empty($post_types))$post_types = array('post');

            $post_types = "'".implode("','",$post_types)."'";


            $sql = "SELECT b.*";
            $sql .= " FROM $wpdb->posts b WHERE b.post_type IN($post_types) AND b.post_status='publish' AND b.post_password='' ";
            $sql .= ' ORDER BY b.post_date ASC LIMIT '.$offset.','.$num;


            $list = $wpdb->get_results($sql);
            if(!$list){
                WB_BSL_Utils::run_log('查询文章0篇','收录查询');
                break;
            }

            $urls = array();
            $post_time = array();
            $post_id = array();

            $ok_num = 0;

            foreach($list as $r){
                $url = get_permalink($r);
                $p_time = strtotime($r->post_date);

                if(get_post_meta($r->ID,'url_in_baidu_ymd',true)){
                    $murl = md5($url);
                    $api = 'http://bsl.api.wbolt.com/baidu/data/'.substr($murl,0,2).'/'.substr($murl,2,2).'/'.$murl.'.txt';
                    $api .= '?t='.$p_time.'&u='.urlencode($url);

                    $arg = array(
                        'timeout'   => 1,
                        //'blocking'  => false,
                        'sslverify' => false,
                    );
                    $http = wp_remote_head($api,$arg);

                    if(!is_wp_error($http)){
                        $code = wp_remote_retrieve_response_code($http);
                        if($code == 200)
                        {
                            $ok_num++;
                            update_post_meta($r->ID,'url_in_baidu','1');
                            update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));

                        }else{
                            update_post_meta($r->ID,'url_in_baidu','2');
                            update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));
                        }
                        continue;
                    }
                }

                $urls[] = $url;
                $post_id[] = $r->ID;
                $post_time[] = $p_time;
                update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));
            }

            $num = count($urls);

            if(empty($urls)){
                if($ok_num>0){
                    WB_BSL_Utils::run_log('查询成功，已收录'.$ok_num.'篇','收录查询');
                }else{
                    WB_BSL_Utils::run_log('查询文章'.$num.'篇','收录查询');
                }
                break;
            }

            WB_BSL_Utils::run_log('查询文章'.$num.'篇','收录查询');

            $arg = array(
                'body'=>array('ver'=>get_option('wb_bsl_ver',0),'url'=>$urls,'post_time'=>$post_time),
                'headers'=>array('referer'=>home_url()),
                'timeout'   => 0.1,
                'blocking'  => false,
                'sslverify' => false,
            );

            $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/bsl',$arg);

            $code = wp_remote_retrieve_response_code($http);

            if($code != 200){
                if(is_wp_error($http)){
                    WB_BSL_Utils::run_log('查询出错，错误【'.$http->get_error_message().'】','收录查询');
                }else{
                    WB_BSL_Utils::run_log('查询出错，状态码【'.$code.'】','收录查询');
                }
                break;
            }

            WB_BSL_Utils::run_log('查询成功，已收录'.$ok_num.'篇','收录查询');
        }while(false);
    }


    public static function wb_push()
    {
        global $wpdb,$wp_post_types;

        if(!WB_BSL_Conf::cnf('in_bd_active')){
            return;
        }
        //新增
        $is_submit = 0;
        do{
            WB_BSL_Utils::run_log('新发布文章或未查询的存量文章','收录查询');


            $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
            if(empty($post_types))$post_types = array('post');

            $post_types = "'".implode("','",$post_types)."'";


            $sql = "SELECT b.*";
            $sql .= " FROM $wpdb->posts b WHERE b.post_type IN($post_types) AND b.post_status='publish' AND b.post_password='' ";
            $sql .= " AND NOT EXISTS(SELECT f.post_id FROM $wpdb->postmeta f WHERE f.post_id=b.ID AND f.meta_key='url_in_baidu')
             AND NOT EXISTS(SELECT j.post_id FROM $wpdb->postmeta j WHERE j.post_id=b.ID AND j.meta_key='url_in_baidu_ymd')
              GROUP BY b.ID LIMIT 1000";


            WB_BSL_Utils::txt_log('wb-query-'.$sql);

            //echo $sql;exit();
            $list = $wpdb->get_results($sql);
            WB_BSL_Utils::txt_log('wb-query-url-num'.count($list));
            if(!$list){
                WB_BSL_Utils::run_log('查询文章0篇','收录查询');
                break;
            }

            $urls = array();
            $post_time = array();
            $post_id = array();
            foreach($list as $r){
                $url = get_permalink($r);
                $urls[] = $url;
                $post_time[] = strtotime($r->post_date);
                $post_id[] = $r->ID;
                update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));
            }

            if(empty($urls)){
                break;
            }

            $num = count($urls);


            WB_BSL_Utils::run_log('查询文章'.$num.'篇','收录查询');

            $arg = array(
                'body'=>array('ver'=>get_option('wb_bsl_ver',0),'url'=>$urls,'post_time'=>$post_time),
                'headers'=>array('referer'=>home_url()),
                'timeout'   => 0.1,
                'blocking'  => false,
                'sslverify' => false,
            );
            $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/bsl',$arg);

            $code = wp_remote_retrieve_response_code($http);

            if($code != 200){
                if(is_wp_error($http)){

                    WB_BSL_Utils::run_log('查询出错，错误【'.$http->get_error_message().'】','收录查询');
                }else{
                    WB_BSL_Utils::run_log('查询出错，状态码【'.$code.'】','收录查询');
                }
                break;
            }
            $is_submit = 1;


        }while(false);


        if($is_submit){
            //return;
        }


        $is_submit = 0;
        do{
            $ymd2 = gmdate('Y-m-d',current_time('timestamp') - 2 * 86400);
            $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
            if(empty($post_types))$post_types = array('post');

            $post_types = "'".implode("','",$post_types)."'";

            $sql = "SELECT b.*";
            $sql .= " FROM $wpdb->posts b,$wpdb->postmeta a WHERE b.ID=a.post_id AND  a.meta_key='url_in_baidu_ymd' AND b.post_type IN($post_types)  AND DATE_FORMAT(a.meta_value,'%Y-%m-%d') < '$ymd2'";
            $sql .= " AND NOT EXISTS(SELECT f.post_id FROM $wpdb->postmeta f WHERE f.post_id=b.ID AND f.meta_key='url_in_baidu')
              GROUP BY b.ID LIMIT 1000";

            $list = $wpdb->get_results($sql);

            if(!$list){
                //WB_BSL_Utils::run_log('查询文章0篇','收录查询');
                break;
            }
            WB_BSL_Utils::run_log('查询未收录文章','收录查询');

            $num = count($list);
            WB_BSL_Utils::run_log('查询到文章'.$num.'篇','收录查询');

            WB_BSL_Utils::txt_log('wb-query-url-num'.$num);
            $urls = array();
            $post_time = array();
            $post_id = array();
            $num = 0;
            foreach($list as $r){
                $url = get_permalink($r);
                $urls[] = $url;
                $p_time = strtotime($r->post_date);
                $post_time[] = $p_time;
                $post_id[] = $r->ID;


                $murl = md5($url);
                $arg = array(
                    'timeout'   => 1,
                    //'blocking'  => false,
                    'sslverify' => false,
                );
                $api = 'http://bsl.api.wbolt.com/baidu/data/'.substr($murl,0,2).'/'.substr($murl,2,2).'/'.$murl.'.txt';

                $api .= '?t='.$p_time.'&u='.urlencode($url);

                $http = wp_remote_head($api,$arg);

                if(is_wp_error($http)){
                    WB_BSL_Utils::run_log('查询出错，错误【'.$http->get_error_message().'】','收录查询');
                    break;
                }
                $code = wp_remote_retrieve_response_code($http);

                if($code == 200)
                {
                    $num++;
                    update_post_meta($r->ID,'url_in_baidu','1');
                }else{
                    update_post_meta($r->ID,'url_in_baidu','2');
                }
                update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));
            }

            WB_BSL_Utils::run_log('查询成功，更新已收录'.$num.'篇','收录查询');

            $is_submit = 1;

        }while(0);

        if($is_submit){
            //return;
        }


        $year_1 = gmdate('Y-m-d',strtotime('-6 month'));

        $ymd7 = gmdate('Y-m-d',current_time('timestamp') - 2 * 86400);
        $ymd14 = gmdate('Y-m-d',current_time('timestamp') - 1 * 86400);

        do{
            WB_BSL_Utils::run_log('重新查询未收录文章','收录查询');


            $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
            if(empty($post_types))$post_types = array('post');

            $post_types = "'".implode("','",$post_types)."'";


            $sql = "SELECT b.*";
            $sql .= " FROM $wpdb->posts b WHERE b.post_type IN($post_types) AND b.post_status='publish' AND b.post_password='' AND DATE_FORMAT(b.post_date,'%Y-%m-%d') > '$year_1' AND DATE_FORMAT(b.post_date,'%Y-%m-%d') < '$ymd7' ";
            $sql .= " AND NOT EXISTS(SELECT f.post_id FROM $wpdb->postmeta f WHERE f.post_id=b.ID AND f.meta_key='url_in_baidu' AND f.meta_value='1')
             AND NOT EXISTS(SELECT j.post_id FROM $wpdb->postmeta j WHERE j.post_id=b.ID AND j.meta_key='url_in_baidu_ymd' AND DATE_FORMAT(j.meta_value,'%Y-%m-%d') > '$ymd14')
              GROUP BY b.ID LIMIT 1000";


            WB_BSL_Utils::txt_log('wb-query-'.$sql);

            $list = $wpdb->get_results($sql);

            if(!$list){
                WB_BSL_Utils::run_log('查询文章0篇','收录查询');
                break;
            }
            $num = count($list);
            WB_BSL_Utils::run_log('查询文章'.$num.'篇','收录查询');

            WB_BSL_Utils::txt_log('wb-query-url-num'.$num);
            $urls = array();
            $post_time = array();
            $post_id = array();
            $num = 0;
            foreach($list as $r){
                $url = get_permalink($r);
                $urls[] = $url;
                $p_time = strtotime($r->post_date);
                $post_time[] = $p_time;
                $post_id[] = $r->ID;


                $murl = md5($url);
                $arg = array(
                    'timeout'   => 1,
                    //'blocking'  => false,
                    'sslverify' => false,
                );
                $api = 'http://bsl.api.wbolt.com/baidu/data/'.substr($murl,0,2).'/'.substr($murl,2,2).'/'.$murl.'.txt';

                $api .= '?t='.$p_time.'&u='.urlencode($url);

                $http = wp_remote_head($api,$arg);

                if(is_wp_error($http)){
                    WB_BSL_Utils::run_log('查询出错，错误【'.$http->get_error_message().'】','收录查询');
                    break;
                }
                $code = wp_remote_retrieve_response_code($http);

                if($code == 200)
                {
                    $num++;
                    update_post_meta($r->ID,'url_in_baidu','1');
                }else{
                    update_post_meta($r->ID,'url_in_baidu','2');
                }
                update_post_meta($r->ID,'url_in_baidu_ymd',current_time('mysql'));
            }

            WB_BSL_Utils::run_log('查询成功，更新已收录'.$num.'篇','收录查询');


        }while(false);
    }


    public static function update_index($d){
        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';

        $ymd = current_time('Y-m-d');
        $row = $wpdb->get_row("SELECT * FROM $t WHERE ymd='$ymd' AND `type`=1");

        if($row){
            if($row->limited > 0 && isset($d['limited'])){
                unset($d['limited']);
                unset($d['remain']);
            }
            $wpdb->update($t,$d,array('id'=>$row->id));
        }else{
            $d['ymd'] = $ymd;
            $d['type']=1;
            $wpdb->insert($t,$d);
        }
    }


    public static function baidu_index($force = false){


        $h = current_time('H');
        if(!$force && !in_array($h,array('00','06','12','18','23'))){
            return;
        }

        WB_BSL_Utils::run_log('查询百度收录概况-“收录总数”，请求接口：','收录概况');
        //所有
        $all_in = WB_BSL_Baidu::scrapy_index();
        $wb_idx = null;
        if(!$wb_idx && $all_in<0){
            $wb_idx = self::wb_idx();
            if($wb_idx && isset($wb_idx['all_index']) && $wb_idx['all_index']> -1){
                $all_in = $wb_idx['all_index'];
            }
        }

        $d = array();
        if($all_in>-1){
            update_option('wb_idx_data_updated',current_time('mysql'),false);
            $d['all_in'] = $all_in;
        }
        $to = current_time('timestamp');
        //当天
        $from = strtotime(current_time('Y-m-d'));
        /*
        sleep(mt_rand(1,2));
        $day_in = WB_BSL_Baidu::scrapy_index(null,$from,$to);
        if($day_in>-1){
            $d['day_in'] = $day_in;
        }*/

        do{

            if(!$all_in){
                $d['month_in'] = 0;
                $d['week_in'] = 0;
                break;
            }

            WB_BSL_Utils::run_log('查询百度收录概况-“近30天收录”概况，请求接口：','收录概况');
            $from2 = $from - 29 * 86400;
            sleep(mt_rand(1,2));
            $month_in = WB_BSL_Baidu::scrapy_index(null,$from2,$to);
            if($month_in<0){
                if(!$wb_idx){
                    $wb_idx = self::wb_idx();
                }
                if($wb_idx && isset($wb_idx['month_index']) && $wb_idx['month_index']> -1){
                    $month_in = $wb_idx['month_index'];
                }
            }

            if($month_in>-1){
                update_option('wb_idx_data_updated',current_time('mysql'),false);
                $d['month_in'] = $month_in;
            }

            if(!$month_in){
                $d['week_in'] = 0;
                break;
            }


            //7天
            WB_BSL_Utils::run_log('查询百度收录概况-“近7天收录”概况，请求接口：','收录概况');

            $from2 = $from - 6 * 86400;
            sleep(mt_rand(1,2));
            $week_in = WB_BSL_Baidu::scrapy_index(null,$from2,$to);

            if($week_in<0){
                if(!$wb_idx){
                    $wb_idx = self::wb_idx();
                }
                if($wb_idx && isset($wb_idx['week_index']) && $wb_idx['week_index']> -1){
                    $week_in = $wb_idx['week_index'];
                }
            }

            if($week_in>-1){
                update_option('wb_idx_data_updated',current_time('mysql'),false);
                $d['week_in'] = $week_in;
            }

        }while(0);





        //new_in
        $d['new_in'] = self::get_day_inc($d['all_in']);

        //not_in
        $d['not_in'] = self::un_index();

        $d['limited'] = self::last_limited();
        $d['remain'] = $d['limited'];

        self::update_index($d);

    }

    public static function wb_idx($type=0)
    {
        $data = array('all_index'=>-1,'week_index'=>-1,'month_index'=>-1);
        if($type==1){
            $data = array('num'=>-1,'query_times'=>-1);
        }
        do{
            $ver = get_option('wb_bsl_ver',0);
            if(!$ver){
                break;
            }
            $host = parse_url(home_url(),PHP_URL_HOST);
            $body = array('ver'=>$ver,'host'=>$host);
            if($type==1){
                $body['type'] = 1;
            }
            $arg = array(
                'body'=>$body,
                'headers'=>array('referer'=>home_url()),
                'timeout'   => 5,
                'sslverify' => false,
            );

            $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/bsl/idx',$arg);

            if(is_wp_error($http)){
                break;
            }
            $body = wp_remote_retrieve_body($http);

            if(!$body){
                break;
            }

            $ret = json_decode($body,true);
            if(!$ret){
                break;
            }
            if(isset($ret[0]) && $ret[0]){
                break;
            }

            if(!isset($ret[1]) || !is_array($ret[1])){
                break;
            }

            $data = $ret[1];

        }while(0);

        return $data;
    }

    public static function calc_limited(){

        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';
        $log = $wpdb->prefix.'wb_bsl_log';

        $ymd = current_time('Y-m-d');

        $num = $wpdb->get_var("SELECT COUNT(1) FROM  $log WHERE DATE_FORMAT(create_date,'%Y-%m-%d') = '$ymd' AND `type` = 2 AND push_status=1 ");

        if($num>0){
            $wpdb->query("UPDATE $t SET `limited` = `remain` + $num WHERE ymd='$ymd' AND `type`=1");
        }



        return $num;
    }


    public static function last_limited(){

        global $wpdb;

        //$row = array('limited'=>10,'remain'=>10);

        $t = $wpdb->prefix.'wb_bsl_day';

        $list = $wpdb->get_results("SELECT * FROM $t WHERE `type`=1 ORDER BY ymd DESC LIMIT 2");
        if(!$list){
            return 10;
        }
        $num = 10;
        $ymd = current_time('Y-m-d');
        foreach($list as $r){
            if($r->ymd != $ymd){
                $num = $r->limited;
                break;
            }
        }

        return $num;

    }

    public static function un_index(){
        global $wpdb;


        $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
        if(empty($post_types))$post_types = array('post');

        $post_types = "'".implode("','",$post_types)."'";

        //收录
        $num = $wpdb->get_var("SELECT COUNT(DISTINCT a.ID) num FROM $wpdb->posts a,$wpdb->postmeta m WHERE a.ID=m.post_id AND m.meta_key='url_in_baidu' AND m.meta_value='1' AND a.post_type IN($post_types)");

        //文章
        $post_num = $wpdb->get_var("SELECT count(1) FROM $wpdb->posts WHERE  post_type IN($post_types) AND post_status='publish'");

        $not_found = max(0,$post_num - $num);

        return $not_found;
    }

    public static function get_day_inc($now){
        global $wpdb;
        $t = $wpdb->prefix.'wb_bsl_day';
        $list = $wpdb->get_results("SELECT * FROM $t WHERE `type`=1 ORDER BY ymd DESC LIMIT 2");
        if(!$list){
            return 0;
        }
        $num = 0;
        $ymd = current_time('Y-m-d');
        foreach($list as $r){
            if($r->ymd != $ymd){
                $num = max(0,$now - $r->all_in);
                break;
            }
        }

        return $num;
    }


}