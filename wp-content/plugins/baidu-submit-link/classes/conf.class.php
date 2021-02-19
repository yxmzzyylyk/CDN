<?php

/**
 * 插件配置
 * Class WB_BSL_Conf
 */

class WB_BSL_Conf
{

    public static $debug = false;

    public static $name = 'bsl_pack';
    public static $optionName = 'bsl_option';

    public static $db_ver = 12;


    public static function cnf($key,$default=null){
        static $_push_cnf = array();
        if(!$_push_cnf){
            $def = array(
                //base
                'post_type'=>array('post'),
                'check_404'=>0,
                'in_bd_active'=>0,
                'log_day'=>7,
                //baidu
                'token'=>'',
                'pc_active'=>0,
                'bdauto'=>0,
                'daily_active'=>0,
                //bing
                'bing_key'=>'',
                'bing_auto'=>0,
                'bing_manual'=>0,
                //360
                'qh_active'=>0,
                'qhjs'=>'',
                'qh_batch'=>0,

                //
                'app_active'=>0,
                'app_id'=>'',
                'app_token'=>'',
                'daily_api'=>'',
                'use_daily'=>0,
            );
            $_push_cnf = get_option(self::$optionName,array());

            //print_r($_push_cnf);

            if(isset($_push_cnf['daily_api']) && isset($_push_cnf['token'])){
                if(!$_push_cnf['token'] && $_push_cnf['daily_api']){
                    $_push_cnf['token'] = $_push_cnf['daily_api'];
                }
            }

            if(!isset($_push_cnf['pc_active'])){
                $_push_cnf['pc_active'] = 0;
            }

            $api = '';
            do{
                if(!isset($_push_cnf['token']) || !$_push_cnf['token']){
                    break;
                }
                $token = $_push_cnf['token'];
                if(preg_match('#^https?://#',$token)){
                    $url = parse_url($token);
                    if($url['host'] != 'data.zz.baidu.com'){
                        break;
                    }
                    parse_str($url['query'],$param);
                    if(!isset($param['site']) || !isset($param['token'])){
                        break;
                    }
                    $api = 'http://data.zz.baidu.com/urls?site='.$param['site'].'&token='.$param['token'];
                }else{
                    $api = 'http://data.zz.baidu.com/urls?site='.home_url().'&token='.$token.'';
                }

            }while(0);

            $_push_cnf['token'] = $api;
            $_push_cnf['daily_api'] = $api?$api.'&type=daily':'';

            if(!$api){
                $_push_cnf['pc_active'] = 0;
                $_push_cnf['daily_active'] = 0;
            }


            if(isset($_push_cnf['qhjs']) && $_push_cnf['qhjs']){
                $_push_cnf['qh_active'] = 1;
            }

            //print_r($_push_cnf);



            self::extend_conf($_push_cnf,$def);
        }

        if(null === $key){
            return $_push_cnf;
        }
        if(isset($_push_cnf[$key])){
            return $_push_cnf[$key];
        }

        return $default;

    }

    public static function update_cnf()
    {
        if(isset($_POST['opt']) && $_POST['opt']){
            $opt = $_POST['opt'];
            if(isset($opt['qhjs'])){
                $opt['qhjs'] = stripslashes($opt['qhjs']);
                $opt['qh_active'] = 1;
            }

            $opt_data = self::cnf(null);
            foreach($opt_data as $k=>$v){
                if(isset($opt[$k])){
                    $opt_data[$k] = $opt[$k];
                    continue;
                }
                unset($opt_data[$k]);
            }

            update_option( self::$optionName, $opt_data );
        }
    }

    public static function extend_conf(&$cnf,$conf){
        if(is_array($conf))foreach($conf as  $k=>$v){
            if(!isset($cnf[$k])){
                $cnf[$k] = $v;
            }else if(is_array($v)){
                if(!is_array($cnf[$k])){
                    $cnf[$k] = array();
                }
                self::extend_conf($cnf[$k],$v);
            }
        }
    }


    public static function check_post_type($post){

        if($post->post_status != 'publish'){
            return false;
        }

        if($post->post_password != ''){
            return false;
        }

        $post_types = self::cnf('post_type',array('post'));

        if(empty($post_types))$post_types = array('post');

        if(!in_array($post->post_type,$post_types)){
            return false;
        }
        return true;
    }

    public static function setup_db(){
        global $wpdb;

        $wb_tables = explode(',','wb_bsl_day,wb_bsl_log,wb_bsl_stats');

        //数据表
        $tables = $wpdb->get_col("SHOW TABLES LIKE '".$wpdb->prefix."wb_bsl_%'");


        $set_up = array();
        foreach ($wb_tables as $table){
            if(in_array($wpdb->prefix.$table,$tables)){
                continue;
            }

            $set_up[] = $table;
        }

        if(empty($set_up)){
            return;
        }

        WB_BSL_Utils::create_wb_table($set_up,self::install_sql());


        update_option('wb_bsl_db_ver',self::$db_ver,false);


    }

    public static function upgrade_db_12()
    {
        $db_ver = (int)get_option('wb_bsl_db_ver',0);
        if($db_ver<12){
            self::setup_db();
        }
    }

    public static function upgrade_v3_conf(&$err=''){

        do{
            $siteurl = get_option('siteurl');
            if(!$siteurl){
                $siteurl = home_url();
            }

            $host = parse_url($siteurl,PHP_URL_HOST);

            $param = array(
                'code'=>get_option('wb_bsl_ver',0),
                'host'=>$host,
                'ver'=>'bsl-pro',
            );

            if(!$param['code']){
                $err = '升级异常，请稍后再试。';
                break;
            }

            $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/update',array('sslverify'=>false,'body'=>$param,'headers'=>array('referer'=>home_url()),));
            if(is_wp_error($http)){
                $err = '升级异常，请稍后再试。[1000]['.$http->get_error_message().']';
                break;
            }

            if($http['response']['code']!=200){
                $err = '升级异常，请稍后再试。[1001]['.$http['response']['code'].']';
                break;
            }

            $body = $http['body'];

            if(empty($body)){
                $err = '升级异常，请稍后再试。[1002]';
                break;
            }

            $data = json_decode($body,true);

            if(empty($data)){
                $err = '升级异常，请稍后再试。[1003]';
                break;
            }
            if(empty($data['data'])){
                $err = '升级异常，请稍后再试。[1004]';
                break;
            }
            if($data['code']){
                $err = '升级异常，请稍后再试。['.$data['data'].']';
                break;
            }

            update_option('wb_bsl_cnf_'.$data['v'],$data['data'],false);

            return true;

        }while(false);

        return false;
    }


    public static function check_tb_exists(){
        global $wpdb;


        $wb_tables = explode(',','wb_bsl_day,wb_bsl_log');

        //数据表
        $tables = $wpdb->get_col("SHOW TABLES LIKE '".$wpdb->prefix."wb_bsl_%'");

        return count($wb_tables) == count($tables);
    }




    public static function install_sql(){
        $sql =  'CREATE TABLE IF NOT EXISTS `wp_wb_bsl_day` (
                  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ymd` date NOT NULL,
                  `all_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `new_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `not_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `day_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `week_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `month_in` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `limited` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `remain` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT \'1\',
                  PRIMARY KEY (`id`),
                  KEY `ymd` (`ymd`)
                ) ENGINE=InnoDB;
                -- row split --
                CREATE TABLE IF NOT EXISTS `wp_wb_bsl_log` (
                  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `post_id` bigint(20) UNSIGNED NOT NULL,
                  `post_url` varchar(256) DEFAULT NULL,
                  `push_status` tinyint(4) NOT NULL,
                  `index_status` tinyint(4) NOT NULL,
                  `create_date` datetime DEFAULT NULL,
                  `type` tinyint(4) NOT NULL DEFAULT \'1\',
                  `result` text,
                  PRIMARY KEY (`id`),
                  KEY `post_id` (`post_id`,`type`),
                  KEY `push_status` (`push_status`,`type`),
                  KEY `index_status` (`index_status`)
                ) ENGINE=InnoDB;
                -- row split --
                CREATE TABLE IF NOT EXISTS `wp_wb_bsl_stats` (
                  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                  `ymd` date NOT NULL,
                  `num1` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num2` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num3` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num4` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num5` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num6` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num7` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `num8` int(10) UNSIGNED NOT NULL DEFAULT \'0\',
                  `type` tinyint(3) UNSIGNED NOT NULL DEFAULT \'1\',
                  PRIMARY KEY (`id`),
                  KEY `ymd` (`ymd`),
                  KEY `type` (`type`)
                ) ENGINE=InnoDB;
                ';

        return $sql;

    }


}