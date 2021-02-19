<?php


/**
 * 统计
 * Class WB_BSL_Stats
 */

class WB_BSL_Stats
{

    public static $type2field = array(1=>'num1',2=>'num2',3=>'num3',4=>'num4',10=>'num5',11=>'num6',20=>'num7');

    public static function inc_num($type,$num=1,$ymd=null){
        global $wpdb;
        if(!$ymd){
            $ymd = current_time('Y-m-d');
        }
        $fields = self::$type2field;
        $field = 'num1';
        if(isset($fields[$type])){
            $field = $fields[$type];
        }

        $t = $wpdb->prefix.'wb_bsl_stats';
        $id = $wpdb->get_var($wpdb->prepare("SELECT id FROM $t WHERE ymd=%s AND `type`=1",$ymd));
        if($id){
            $wpdb->query($wpdb->prepare("UPDATE $t SET $field = $field + %d WHERE id=%d",$num,$id));
        }else{
            $d = array('type'=>1,'ymd'=>$ymd);
            $d[$field] = $num;
            $wpdb->insert($t,$d);
        }
    }

    public static function action_add_push_log($d)
    {
        if(isset($d['push_status']) && $d['push_status']){
            self::inc_num($d['type']);
        }

    }


    public static function pc_stat_data($day=7){

        $ret = array();

        $data_1 = self::push_stat_data(4,$day);
        $data_2 = self::push_stat_data(1,$day);

        $ret[] = array_values($data_1);
        $ret[] = array_values($data_2);


        return $ret;
    }

    public static function day_push_data($day=7){
        $ret = array();
        $data_1 = self::push_stat_data(2,$day);
        $data = self::index_data($day);

        $ret[] = array_values($data['limited']);
        $ret[] = array_values($data_1);
        $ret[] = array_values($data['remain']);
        return $ret;
    }


    public static function daily_push_data($day=7){
        $ret = array();
        $data_1 = self::push_stat_data(5,$day);
        $data = self::index_data($day);

        $ret[] = array_values($data['limited']);
        $ret[] = array_values($data_1);
        $ret[] = array_values($data['remain']);
        return $ret;
    }

    public static function week_push_data($day=7){
        $ret = array();
        $data_1 = self::push_stat_data(3,$day);
        $ret[] = array_values($data_1);
        return $ret;
    }


    public static function push_stat_data($type,$day=7){
        global $wpdb;

        $fields = self::$type2field;
        $filed = 'num1';
        if(isset($fields[$type])){
            $filed = $fields[$type];
        }

        $t = $wpdb->prefix.'wb_bsl_stats';

        //$now = current_time('Y-m-d');
        $timestamp = current_time('U');

        //$from_timestamp = strtotime((1-$day).' day',$timestamp);

        //$from = gmdate('Y-m-d',$from_timestamp);

        $from_time = $timestamp - ($day-1) * 86400;


        $sql = "SELECT DATE_FORMAT(ymd,'%m-%d') ymd ,$filed FROM $t WHERE `type`=1  AND ymd BETWEEN DATE_ADD(NOW(),INTERVAL -$day DAY) AND NOW() ORDER BY ymd ASC";

        $ret = array();

        for($i=0;$i<$day;$i++){
            $ret[gmdate('m-d',$from_time + $i * 86400)] = 0;
        }

        $list = $wpdb->get_results($sql);

        foreach ($list as $r){
            $ret[$r->ymd] = $r->{$filed};
        }

        return $ret;
    }

    public static function push_stat_data_log($type,$day=7){
        global $wpdb;


        $t = $wpdb->prefix.'wb_bsl_log';

        $now = current_time('mysql');
        $timestamp = current_time('timestamp');

        $from_timestamp = strtotime((1-$day).' day',$timestamp);

        $from = gmdate('Y-m-d 00:00:00',$from_timestamp);


        $sql = "SELECT DATE_FORMAT(create_date,'%m-%d') ymd ,COUNT(1) num FROM $t WHERE push_status=1 AND `type`=$type AND create_date BETWEEN '$from' AND '$now' GROUP BY ymd ORDER BY ymd ASC";

        $ret = array();

        for($i=0;$i<$day;$i++){
            $ret[gmdate('m-d',$from_timestamp + $i * 86400)] = 0;
        }

        $list = $wpdb->get_results($sql);

        foreach ($list as $r){
            $ret[$r->ymd] = $r->num;
        }

        return $ret;
    }

    public static function baidu_log($type,$num=10,$offset=0){
        global $wpdb;

        $post_types = WB_BSL_Conf::cnf('post_type',array('post'));
        if(empty($post_types))$post_types = array('post');

        $post_types = "'".implode("','",$post_types)."'";
        $limit = $offset.','.$num;




        if($type==1){
            $sql = "SELECT a.* FROM $wpdb->posts a WHERE a.post_type IN($post_types) AND a.post_status='publish'";
        }else if($type==2){
            $sql = "SELECT a.* FROM $wpdb->posts a,$wpdb->postmeta b WHERE a.ID=b.post_id AND b.meta_key='url_in_baidu' AND b.meta_value='1' AND a.post_type IN($post_types) AND a.post_status='publish'";
        }else if($type==3){
            $sql = "SELECT a.* FROM $wpdb->posts a WHERE a.post_type IN($post_types) AND a.post_status='publish'";
            $sql .= " AND NOT EXISTS(SELECT b.post_id FROM $wpdb->postmeta b WHERE b.post_id = a.ID AND b.meta_key='url_in_baidu' AND b.meta_value='1' )";
        }else{
            return array();
        }

        $sql .= " ORDER BY a.post_date DESC LIMIT $limit";


        //echo $sql;
        $list =  $wpdb->get_results($sql);

        $new_list = array();
        foreach($list as $r){
            $in_baidu = get_post_meta($r->ID,'url_in_baidu',true);
            $d = array(
                'post_id'=>$r->ID,
                'post_url'=>get_permalink($r),
                'post_title'=>$r->post_title,
                'post_date'=>$r->post_date,
                'in_baidu' =>$in_baidu,
                'last_date'=>get_post_meta($r->ID,'url_in_baidu_ymd',true),
            );
            $new_list[] = $d;
        }

        return $new_list;
    }
    public static function push_log($type,$num=10,$offset=0){
        global $wpdb;


        $t = $wpdb->prefix.'wb_bsl_log';

        $now = current_time('mysql');
        $timestamp = current_time('timestamp');

        $from_timestamp = strtotime('-6 day',$timestamp);
        $from = gmdate('Y-m-d 00:00:00',$from_timestamp);

        $limit = $offset.','.$num;

        $sql = "SELECT id,post_id,create_date AS `date`,`post_url` AS `url`,push_status AS `s_push`,index_status AS 's_record',`type`,IF(`result` IS NULL,1,0) is_old FROM $t WHERE `type`=$type AND create_date BETWEEN '$from' AND '$now' ORDER BY id DESC LIMIT $limit";

        $new_list = array();

        $list =  $wpdb->get_results($sql);
        $result = json_encode(array('remain'=>0,'success'=>1));
        foreach($list as $r){

            if($r->type < 4 && $r->is_old){
                $r->url = get_permalink($r->post_id);
                $wpdb->query($wpdb->prepare("UPDATE $t SET post_url = %s,`result`=%s WHERE id=%d",$r->url,$result,$r->id));
            }

            $new_list[] = $r;

        }

        return $new_list;


    }
    public static function day_index($ymd=null){

        global $wpdb;
        $t = $wpdb->prefix.'wb_bsl_day';


        if(!$ymd){

            $ymd = current_time('Y-m-d');
        }

        $fields = array('all_in','new_in','not_in','day_in','week_in','month_in','limited','remain');
        $sql = "SELECT ymd ,".(implode(',',$fields))." FROM $t WHERE  ymd = '$ymd' AND `type`=1 ORDER BY ymd,id DESC";

        $row = $wpdb->get_row($sql);

        if(!$row){
            $row = new stdClass();
            foreach ($fields as $field){
                $row->$field = 0;
            }
        }

        return $row;
    }


    public static function qh_data($day=7)
    {

        $result = array();


        $result['auto'] = self::push_stat_data(20,$day);

        return $result;
    }
    public static function bing_data($day=7)
    {
        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';


        $now = current_time('Y-m-d');
        $timestamp = current_time('timestamp');

        $from_timestamp = strtotime((1-$day).' day',$timestamp);

        $from = gmdate('Y-m-d',$from_timestamp);


        $fields = array('all_in','new_in','not_in','day_in','week_in','month_in','limited','remain');
        $sql = "SELECT DATE_FORMAT(ymd,'%m-%d') md, ymd ,".(implode(',',$fields))." FROM $t WHERE `type`=2 AND ymd BETWEEN '$from' AND '$now' ORDER BY ymd ASC";

        //echo $sql;
        $ret = array();

        for($i=0;$i<$day;$i++){
            $ret[gmdate('m-d',$from_timestamp + $i * 86400)] = 0;
        }

        $result = array();

        foreach ($fields as $field){
            $result[$field] = $ret;
        }
        $result['auto'] = $ret;
        $result['manual'] = $ret;

        $list = $wpdb->get_results($sql);

        foreach ($list as $r){
            //$ret[$r->ymd] = $r->num;
            foreach ($fields as $field){
                $result[$field][$r->md] = $r->$field;
            }
        }



        /*$log = $wpdb->prefix.'wb_bsl_log';
        $log_list = $wpdb->get_results("SELECT COUNT(1) num,DATE_FORMAT(create_date,'%m-%d') AS ymd FROM $log WHERE `type`=10 AND DATE_FORMAT(create_date,'%Y-%m-%d') BETWEEN '$from' AND '$now' GROUP BY ymd ORDER BY ymd ASC");

        if($log_list)foreach ($log_list as $r){
            $result['auto'][$r->ymd] = $r->num;
        }
        $log_list = $wpdb->get_results("SELECT COUNT(1) num,DATE_FORMAT(create_date,'%m-%d') AS ymd FROM $log WHERE `type`=11 AND DATE_FORMAT(create_date,'%Y-%m-%d') BETWEEN '$from' AND '$now' GROUP BY ymd ORDER BY ymd ASC");

        if($log_list)foreach ($log_list as $r){
            $result['manual'][$r->ymd] = $r->num;
        }*/

        $result['auto'] = self::push_stat_data(10,$day);
        $result['manual'] = self::push_stat_data(11,$day);




        //print_r($result);
        return $result;
    }
    public static function index_data($day=7){
        global $wpdb;

        $t = $wpdb->prefix.'wb_bsl_day';


        $now = current_time('Y-m-d');
        $timestamp = current_time('timestamp');

        $from_timestamp = strtotime((1-$day).' day',$timestamp);

        $from = gmdate('Y-m-d',$from_timestamp);


        $fields = array('all_in','new_in','not_in','day_in','week_in','month_in','limited','remain');
        $sql = "SELECT DATE_FORMAT(ymd,'%m-%d') md,ymd ,".(implode(',',$fields))." FROM $t WHERE `type`=1 AND ymd BETWEEN '$from' AND '$now' ORDER BY ymd ASC";

        //echo $sql;
        $ret = array();

        for($i=0;$i<$day;$i++){
            $ret[gmdate('m-d',$from_timestamp + $i * 86400)] = 0;
        }

        $result = array();

        foreach ($fields as $field){
            $result[$field] = $ret;
        }

        $list = $wpdb->get_results($sql);

        foreach ($list as $r){
            //$ret[$r->ymd] = $r->num;
            foreach ($fields as $field){
                $result[$field][$r->md] = $r->$field;
            }
        }


        //print_r($result);
        return $result;


    }


    public static function url_spider($url_md5,$num,$offset=0)
    {
        global $wpdb;
        $limit = '';
        if($num>0){
            $limit = ' LIMIT '.$offset.','.$num;
        }

        $sql = "SELECT `spider`, `visit_date`, `visit_ip` FROM `{$wpdb->prefix}wb_spider_log` WHERE `url_md5`=%s  ORDER BY visit_date DESC $limit";

        $list = $wpdb->get_results($wpdb->prepare($sql,$url_md5));

        return $list;
    }

    public static function spider_404($num=10,$offset=0)
    {

        global $wpdb;
        $limit = '';
        if($num>0){
            $limit = ' LIMIT '.$offset.','.$num;
        }

        $sql = "SELECT MAX(id) id,MAX(visit_date) visit_date, url,`code` FROM `{$wpdb->prefix}wb_spider_log` WHERE `code`=404 AND spider='Baiduspider' GROUP by url_md5 ORDER BY visit_date DESC $limit";

        $list = $wpdb->get_results($sql);

        return $list;
    }



    public static function upgrade_stats_log()
    {

        WB_BSL_Conf::upgrade_db_12();

        global $wpdb;
        $t = $wpdb->prefix.'wb_bsl_log';
        $log = $wpdb->prefix.'wb_bsl_stats';
        $wpdb->query("DELETE FROM $log WHERE EXISTS (SELECT id FROM $t WHERE DATE_FORMAT($t.create_date,'%Y-%m-%d') = $log.ymd )");
        $sql = "SELECT SUM(IF(`type`=1,1,0)) AS num1,
                    SUM(IF(`type`=2,1,0)) AS num2,
                    SUM(IF(`type`=3,1,0)) AS num3,
                    SUM(IF(`type`=4,1,0)) AS num4,
                    SUM(IF(`type`=10,1,0)) AS num5,
                    SUM(IF(`type`=11,1,0)) AS num6
                    ,DATE_FORMAT(create_date,'%Y-%m-%d') ymd 
                    FROM `$t` WHERE 1 GROUP BY ymd";

        $insert = "INSERT INTO $log (`num1`, `num2`, `num3`, `num4`, `num5`, `num6`,`ymd`) $sql";

        $wpdb->query($insert);
    }
}