<?php
/**
 *
 * 插件管理设置
 *
 * @package    WBOLT
 * @author     WBOLT
 * @since      2.1.4
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, WBOLT
 */

class BSL_Admin
{
    public static $name = 'bsl_pack';
    public static $optionName = 'bsl_option';
    public static $debug = false;

    public function __construct(){

    }

    public static function init(){

        self::upgrade();

        register_activation_hook(BSL_BASE_FILE, array(__CLASS__,'plugin_activate'));
        register_deactivation_hook(BSL_BASE_FILE, array(__CLASS__,'plugin_deactivate'));

        if(is_admin()){


            add_action( 'admin_menu', array(__CLASS__,'admin_menu') );
            add_filter( 'plugin_action_links', array(__CLASS__,'actionLinks'), 10, 2 );
            //add_action( 'admin_init', array(__CLASS__,'admin_init') );

            add_action('admin_enqueue_scripts',array(__CLASS__,'admin_enqueue_scripts'),1);

            add_filter('plugin_row_meta', array(__CLASS__, 'plugin_row_meta'), 10, 2);

            add_action('parse_query',array(__CLASS__,'admin_parse_query'));
            add_filter('post_row_actions',array(__CLASS__,'post_row_actions'),99,2);
            add_action('restrict_manage_posts', array(__CLASS__,'restrict_manage_posts'), 10, 2);

            add_action('wp_ajax_wb_baidu_push_url',array(__CLASS__,'wp_ajax_wb_baidu_push_url'));

            add_action( 'add_meta_boxes', array(__CLASS__,'add_meta_box'));

            add_action( 'save_post', array(__CLASS__,'save_post_meta'));

        }

        WB_BSL_Site::init();

        //WB_BSL_App::init();

        WB_BSL_Daily::init();

        WB_BSL_Bing::init();

        //定时任务
        WB_BSL_Cron::init();


        add_action('parse_request', array(__CLASS__, 'parse_request'));


        add_action('wb_bsl_add_push_log',array('WB_BSL_Stats','action_add_push_log'));
    }

    public static function parse_request(){

        if($_SERVER['REQUEST_URI'] == '/404-list.txt'){
            $page = -1;
            $num = 100;
            do{
                $page ++;
                $offset = $page * $num;
                $list = WB_BSL_Stats::spider_404($num,$offset);
                if(!$list){
                    break;
                }
                $url = array();
                foreach($list as $r){
                    $url[] = home_url($r->url);
                }
                if($url){
                    echo implode("\n",$url);
                }

            }while(1);

            exit();
        }

    }

    public static function add_meta_box()
    {
        if(!get_option('wb_bsl_ver',0)){
            return;
        }

        $cnf = WB_BSL_Conf::cnf(null);

        if(!$cnf['app_active'] && !$cnf['daily_active']){
            return;
        }
        add_meta_box(
            'wbolt_meta_box_bsl',
            '百度推送设置',
            array(__CLASS__,'render_meta_box'),
            null,
            'side','high'
        );



    }
    public static function render_meta_box($post)
    {

        $meta_val = get_post_meta($post->ID,'wb_bsl_daily_push',true);

        $html = '<div class="sc-body mt">
        <table class="wbs-form-table">
            <tbody>
            <tr>
                <td class="info">
                <input type="hidden" name="wb_bsl_meta" value="1">
                    <label>
                        <input class="wb-switch" type="checkbox"'.($meta_val?' checked':'').' name="wb_bsl_daily_push">
                        <span class="description mt">不执行快速收录推送</span>
                    </label>
                </td>
            </tr>
            </tbody>
        </table></div>';

        echo $html;

    }

    public static function save_post_meta($post_id)
    {
        if(isset($_POST['wb_bsl_meta'])){
            update_post_meta($post_id,'wb_bsl_daily_push',isset($_POST['wb_bsl_daily_push'])?1:0);
        }
    }

    public static function upgrade(){

        $bsl_ver = get_option('bsl_version','1.0.0');

        if(version_compare($bsl_ver,'3.0.0')<0){
            if(get_option('wb_bsl_ver',0)){
                WB_BSL_Conf::upgrade_v3_conf();
            }
        }
        if(version_compare($bsl_ver,'3.4.9')<0){
            WB_BSL_Stats::upgrade_stats_log();
        }

        if(version_compare($bsl_ver,BSL_VERSION)<0){
            update_option('bsl_version',BSL_VERSION);
        }
    }

    /**
     * 获取推送数据结果
     */
    public static function wp_ajax_wb_baidu_push_url(){

        if (!current_user_can('manage_options')) {
            exit();
        }
        global $wpdb;

        switch ($_REQUEST['do']){

            case 'update_setting':
                WB_BSL_Conf::update_cnf();
                $ret = array('code'=>0,'desc'=>'success');
                header('Content-type:text/json;');
                echo json_encode($ret);
                exit();
                break;
            case 'chk_ver':
                $http = wp_remote_get('https://www.wbolt.com/wb-api/v1/themes/checkver?code=bsl-pro&ver='.BSL_VERSION.'&chk=1',array('sslverify' => false,'headers'=>array('referer'=>home_url()),));

                if(wp_remote_retrieve_response_code($http) == 200){
                    echo wp_remote_retrieve_body($http);
                }

                exit();
                break;
            case 'clear_log':
                $log = self::log_info(1);
                header('content-type:text/json;');

                echo json_encode($log);

                break;
            case 'clean_log':
                $type = isset($_POST['type'])?intval($_POST['type']):0;
                if($type && in_array($type,array(1,2,3,4,5,6,10,11))){
                    WB_BSL_Utils::clean_log($type);
                }

                header('content-type:text/json;');

                echo json_encode(array('success'=>1));

                break;

            case 'reload_log':
                $log = self::log_info();
                header('content-type:text/json;');

                echo json_encode($log);
                exit();
                break;
            case 'check_all_post':

                $param = array('page'=>0,'Ym'=>current_time('Ym'));
                update_option('wb_bsl_check_all',$param,false);


                exit();
                break;
            case 'update_index_data':
                WB_BSL_Utils::run_log('手动更新','收录概况');
                WB_BSL_Cron::baidu_index(1);
                exit();
                break;

            case 'spider_history':
                $ret = array('code'=>0,'data'=>array(),'desc'=>'success');
                $post_id = isset($_POST['post_id'])?absint($_POST['post_id']):0;
                $list = array();
                do{
                    if(!$post_id){
                        break;
                    }
                    $url = get_permalink($post_id);
                    $url = str_replace(home_url(),'',$url);
                    $url_md5 = md5($url);
                    $list = WB_BSL_Stats::url_spider($url_md5,0);

                }while(0);

                include BSL_PATH.'/tpl/url_spider.php';



                exit();

                break;

            case 'check_sitemap':
                $ret = array('code'=>0,'desc'=>'success');

                $site_map = home_url('/sitemap.xml');
                $site_map_exists = '';
                $http = wp_remote_head($site_map);
                //print_r($http);

                if(wp_remote_retrieve_response_code($http) === 200){
                    $site_map_exists = $site_map;
                }
                if(!$site_map_exists){
                    $site_map = home_url('/sitemaps.xml');
                    $http = wp_remote_head($site_map);
                    if(wp_remote_retrieve_response_code($http) === 200){
                        $site_map_exists = $site_map;
                    }

                }
                if(!$site_map_exists){
                    $site_map = home_url('/sitemap_index.xml');
                    $http = wp_remote_head($site_map);
                    if(wp_remote_retrieve_response_code($http) === 200){
                        $site_map_exists = $site_map;
                    }

                }
                if(!$site_map_exists){
                    $site_map = home_url('/wp-sitemap.xml');
                    $http = wp_remote_head($site_map);
                    if(wp_remote_retrieve_response_code($http) === 200){
                        $site_map_exists = $site_map;
                    }

                }
                if(!$site_map_exists){
                    $ret['code'] = 1;
                    $ret['desc'] = '404';
                }else{
                    $ret['desc'] = '200';
                    $ret['data'] = $site_map_exists;
                }

                header('content-type:text/json;');

                echo json_encode($ret);

                exit();

                break;

            case 'down_404_url':
                $list = WB_BSL_Stats::spider_404(0,0);
                $filename = '404-url.txt';
                header('Content-Type: application/application/octet-stream	');
                header('Content-Disposition: attachment;filename="'.$filename.'"');
                header('Cache-Control: max-age=0');
                header('Cache-Control: max-age=1');
                header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
                header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
                header ('Pragma: public'); // HTTP/1.0
                $fileHandle = fopen('php://output', 'wb+');
                foreach($list as $r){
                    fwrite($fileHandle,home_url($r->url)."\n");
                }
                fclose($fileHandle);
                exit();
                break;

            case 'del_404_url':
                $url = sanitize_text_field($_REQUEST['url']);
                try{
                    $wpdb->delete($wpdb->prefix.'wb_spider_log',['code'=>404,'spider'=>'Baiduspider','url_md5'=>md5($_REQUEST['url'])]);
                }catch (Exception $ex){

                }
                header('content-type:text/json');
                echo json_encode(array('code'=>0,'desc'=>'success'));
                break;
            case 'check_404_url':
                $url = sanitize_text_field($_REQUEST['url']);
                header('content-type:text/json;');
                $req_url = home_url($url);
                $http = wp_remote_head($req_url);

                $http_code = 0;
                $code = -1;
                $msg = '检测失败';
                if(!is_wp_error($http)){
                    $http_code = wp_remote_retrieve_response_code($http);
                    $code = 0;
                    $msg = '检测成功';
                    try{
                        $id = intval($_REQUEST['id']);
                        if($id){
                            $wpdb->update($wpdb->prefix.'wb_spider_log',['visit_date'=>current_time('mysql'),'code'=>$http_code],['id'=>$id]);
                            $wpdb->update($wpdb->prefix.'wb_spider_log',['code'=>$http_code],['url_md5'=>md5($_REQUEST['url'])]);
                        }
                    }catch (Exception $ex){

                    }
                }else{
                    $msg = $msg .','. $http->get_error_message();
                }
                header('content-type:text/json');
                echo json_encode(array('code'=>$code,'desc'=>$msg,'data'=>['code'=>$http_code,'visit_date'=>current_time('mysql')]));

                break;
            case 'sp_404_url':
                $num = 10;
                if(isset($_GET['num']) && $_GET['num']){
                    $num = intval($_GET['num']);
                    $num = $num?$num:10;
                }
                $offset = 0;
                if(isset($_GET['offset']) && $_GET['offset']){
                    $offset = absint($_GET['offset']);
                }


                $list = WB_BSL_Stats::spider_404($num,$offset);
                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$list));
                break;
            case 'push_log':
                $type = 1;
                if(isset($_GET['type'])){
                    $type = intval($_GET['type']);
                }
                $num = 10;
                if(isset($_GET['num']) && $_GET['num']){
                    $num = intval($_GET['num']);
                    $num = $num?$num:10;
                }
                $offset = 0;
                if(isset($_GET['offset']) && $_GET['offset']){
                    $offset = absint($_GET['offset']);
                }


                $list = WB_BSL_Stats::push_log($type,$num,$offset);
                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$list));
                break;


            case 'baidu_log':
                $type = 1;
                if(isset($_GET['type'])){
                    $type = intval($_GET['type']);
                }
                $num = 10;
                if(isset($_GET['num']) && $_GET['num']){
                    $num = intval($_GET['num']);
                    $num = $num?$num:10;
                }
                $offset = 0;
                if(isset($_GET['offset']) && $_GET['offset']){
                    $offset = absint($_GET['offset']);
                }


                $list = WB_BSL_Stats::baidu_log($type,$num,$offset);
                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$list));
                break;
            case 'index_stat':
                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }
                $ret = WB_BSL_Stats::index_data($day);
                $data = array(array_values($ret['all_in']),array_values($ret['new_in']),array_values($ret['not_in']));

                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$data));

                //exit();

                break;
            case 'bing_push_manual':

                if(isset($_POST['url']) && $_POST['url']){
                    $ret = WB_BSL_Bing::push_batch_url($_POST['url']);
                    header('content-type:text/json;');

                    echo json_encode($ret);
                }


                exit();

                break;
            case 'bing_quota':
                $ret = array('code'=>1);
                $quota = WB_BSL_Bing::get_quota($ret);
                header('content-type:text/json;');

                echo json_encode($ret);
                break;
            case 'bing_summary':
                $data = WB_BSL_Bing::summary();
                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$data));
                break;
            case 'bing_stat':
                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }
                $ret = WB_BSL_Stats::bing_data($day);
                $data = array(array_values($ret['auto']),array_values($ret['manual']),array_values($ret['remain']));

                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$data));
                break;
            case 'qh_stat':
                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }
                $ret = WB_BSL_Stats::qh_data($day);
                $data = array(array_values($ret['auto']));

                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$data));
                break;
            case 'push_stat':

                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }
                $ret = WB_BSL_Stats::pc_stat_data($day);

                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$ret));

                //exit();
                break;

            case 'day_push_stat':

                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }

                $ret = WB_BSL_Stats::day_push_data($day);


                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$ret));

                //exit();

                break;

            case 'daily_push_stat':

                $day = 7;
                if(isset($_GET['day']) && $_GET['day']==30){
                    $day = 30;
                }

                $ret = WB_BSL_Stats::daily_push_data($day);


                header('content-type:text/json;');

                echo json_encode(array('code'=>0,'data'=>$ret));


                break;



            case 'verify':
                if(!wp_verify_nonce($_POST['_ajax_nonce'], 'wp_ajax_wb_baidu_push_url')){

                    echo json_encode(array('code'=>1,'data'=>'非法操作'));
                    exit(0);
                }
                if(!current_user_can('manage_options')){
                    echo json_encode(array('code'=>1,'data'=>'没有权限'));
                    exit(0);
                }

                $param = array(
                    'code'=>sanitize_text_field(trim($_POST['key'])),
                    'host'=>sanitize_text_field(trim($_POST['host'])),
                    'ver'=>'bsl-pro',
                );
                $err = '';
                do{
                    $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/verify',array('sslverify'=>false,'body'=>$param,'headers'=>array('referer'=>home_url()),));
                    if(is_wp_error($http)){
                        $err = '校验失败，请稍后再试（错误代码001['.$http->get_error_message().'])';
                        break;
                    }

                    if($http['response']['code']!=200){
                        $err = '校验失败，请稍后再试（错误代码001['.$http['response']['code'].'])';
                        break;
                    }

                    $body = $http['body'];

                    if(empty($body)){
                        $err = '发生异常错误，联系<a href="https://www.wbolt.com/member?act=enquire" target="_blank">技术支持</a>（错误代码 010）';
                        break;
                    }

                    $data = json_decode($body,true);

                    if(empty($data)){
                        $err = '发生异常错误，联系<a href="https://www.wbolt.com/member?act=enquire" target="_blank">技术支持</a>（错误代码011）';
                        break;
                    }
                    if(empty($data['data'])){
                        $err = '校验失败，请稍后再试（错误代码004)';
                        break;
                    }
                    if($data['code']){
                        $err_code = $data['data'];
                        switch ($err_code){
                            case 100:
                            case 101:
                            case 102:
                            case 103:
                                $err = '插件配置参数错误，联系<a href="https://www.wbolt.com/member?act=enquire" target="_blank">技术支持</a>（错误代码'.$err_code.'）';
                                break;
                            case 200:
                                $err = '输入key无效，请输入正确key（错误代码200）';
                                break;
                            case 201:
                                $err = 'key使用次数超出限制范围（错误代码201）';
                                break;
                            case 202:
                            case 203:
                            case 204:
                                $err = '校验服务器异常，联系<a href="https://www.wbolt.com/member?act=enquire" target="_blank">技术支持</a>（错误代码'.$err_code.'）';
                                break;
                            default:
                                $err = '发生异常错误，联系<a href="https://www.wbolt.com/member?act=enquire" target="_blank">技术支持</a>（错误代码'.$err_code.'）';
                        }

                        break;
                    }

                    update_option('wb_bsl_ver',$data['v'],false);
                    update_option('wb_bsl_cnf_'.$data['v'],$data['data'],false);


                    echo json_encode(array('code'=>0,'data'=>'success'));
                    exit(0);
                }while(false);
                echo json_encode(array('code'=>1,'data'=>$err));
                //exit(0);
                break;
            case 'options':
                if(!current_user_can('manage_options') || !wp_verify_nonce($_GET['_ajax_nonce'], 'wp_ajax_wb_baidu_push_url')){
                    echo json_encode(array('o'=>''));
                    exit(0);
                }

                $ver = get_option('wb_bsl_ver',0);
                $cnf = '';
                if($ver){
                    $cnf = get_option('wb_bsl_cnf_'.$ver,'');
                }
                $list = array('o'=>$cnf);
                header('content-type:text/json;charset=utf-8');
                echo json_encode($list);
                //exit();
                break;


        }

        exit();


    }

    public static function admin_menu(){

        add_menu_page(
            '搜索推送管理插件',
            '搜索推送',
            'administrator',
            'wb_bsl' ,
            array(__CLASS__,'bsl_stats'),
	        plugin_dir_url(BSL_BASE_FILE). 'assets/icon_for_menu.svg'
        );
        add_submenu_page('wb_bsl','数据统计 - 搜索推送', '数据统计', 'administrator','wb_bsl' , array(__CLASS__,'bsl_stats'));
        add_submenu_page('wb_bsl','推送日志 - 搜索推送', '推送日志', 'administrator','wb_bsl_log' , array(__CLASS__,'bsl_log'));
        add_submenu_page('wb_bsl','插件设置 - 搜索推送', '插件设置', 'administrator','wb_bsl_cnf' , array(__CLASS__,'bsl_cnf'));
        add_submenu_page('wb_bsl','Pro版本 - 搜索推送', 'Pro版本', 'administrator','wb_bsl_pro' , array(__CLASS__,'bsl_pro'));
    }

    public static function bsl_stats()
    {
        global $wpdb;

        if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
            WB_Core_Asset_Load::load('stats-02');
        }else{
            $dir_url = plugin_dir_url(BSL_BASE_FILE);
            wp_enqueue_script('wb-bsl-js', $dir_url . 'assets/bsl_stats.js', array(), BSL_VERSION, true);
        }
        $wb_ajax_nonce = wp_create_nonce('wp_ajax_wb_baidu_push_url');
        $in_line_js = array();

        $in_line_js['wb_bsl_init'] = 0;
        $in_line_js['wb_bsl_cnf'] = '{}';
        $in_line_js['_wb_bsl_ajax_nonce'] = sprintf("'%s'",$wb_ajax_nonce);
        $in_line_js['pd_code'] = sprintf("'%s'",self::$optionName);

        $init_data = array(
            'spider_install'=>1,
            'spider_setup_url'=>admin_url('plugin-install.php?s=Wbolt+Spider+Analyser&tab=search&type=term'),
            'spider_active'=>1,
            'spider_active_url'=>admin_url('plugin-install.php?s=Wbolt+Spider+Analyser&tab=search&type=term'),
        );
        //check wb spider
        $init_data['spider_install'] = file_exists(WP_CONTENT_DIR.'/plugins/spider-analyser/index.php');
        if($init_data['spider_install']){
            $init_data['spider_active'] = class_exists('WP_Spider_Analyser');
        }

        $in_line_js['bsl_data'] = json_encode($init_data,JSON_UNESCAPED_UNICODE);


        $row = WB_BSL_Stats::day_index();
        $data = array(
            array('name'=>'收录总数','value'=>$row->all_in),
            array('name'=>'近7天收录','value'=>$row->week_in),
            array('name'=>'近30天收录','value'=>$row->month_in),
        );
        $in_line_js['base_overview'] = json_encode($data,JSON_UNESCAPED_UNICODE);
        $in_line_js['bsl_opt'] = json_encode(WB_BSL_Conf::cnf(null),JSON_UNESCAPED_UNICODE);

        $query_times = get_option('wb_bsl_query_times','');
        if(!$query_times || !is_array($query_times)){
            $query_times = array('time'=>0,'times'=>0);
        }

        if($query_times['time']<current_time('U',1)){
            $query_times = array('time'=>current_time('U',1)+86400,'times'=>$query_times['times']);
            $data = WB_BSL_Cron::wb_idx(1);
            if($data && isset($data['query_times'])){
                $query_times['times'] = $data['query_times'];
            }
            update_option('wb_bsl_query_times',$query_times,false);
        }


        $last_check_date = $wpdb->get_var("SELECT max(meta_value) FROM $wpdb->postmeta WHERE meta_key='url_in_baidu_ymd'");
        $in_line_js['last_check_date'] = sprintf("'%s'",$last_check_date);
        $in_line_js['last_query_times'] = sprintf("'%s'",$query_times['times']);
        $wb_bsl_check_all = get_option('wb_bsl_check_all',0);
        $in_line_js['baidu_check_all'] = ($wb_bsl_check_all?1:0);


        $js = [];
        foreach($in_line_js as $var=>$value){
            $js[] = $var.' = '.$value;
        }
        wp_add_inline_script('wb-bsl-js',' var '.implode(",\n",$js).';','before');

        include BSL_PATH.'/tpl/stats.php';
    }
    public static function bsl_log()
    {
        global $wpdb;

        if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
            WB_Core_Asset_Load::load('log-02');
        }else{
            $dir_url = plugin_dir_url(BSL_BASE_FILE);
            wp_enqueue_script('wb-bsl-js', $dir_url . 'assets/bsl_log.js', array(), BSL_VERSION, true);
        }
        $wb_ajax_nonce = wp_create_nonce('wp_ajax_wb_baidu_push_url');
        $in_line_js = array();

        $in_line_js['wb_bsl_init'] = 0;
        $in_line_js['wb_bsl_cnf'] = '{}';
        $in_line_js['_wb_bsl_ajax_nonce'] = sprintf("'%s'",$wb_ajax_nonce);
        $in_line_js['pd_code'] = sprintf("'%s'",self::$optionName);
        $in_line_js['bsl_opt'] = json_encode(WB_BSL_Conf::cnf(null),JSON_UNESCAPED_UNICODE);

        $js = [];
        foreach($in_line_js as $var=>$value){
            $js[] = $var.' = '.$value;
        }
        wp_add_inline_script('wb-bsl-js',' var '.implode(",\n",$js).';','before');

        include BSL_PATH.'/tpl/log.php';
    }
    public static function bsl_cnf()
    {
        global  $wp_post_types;

        if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
            WB_Core_Asset_Load::load('cnf-02');
        }else{
            $dir_url = plugin_dir_url(BSL_BASE_FILE);
            wp_enqueue_script('wb-bsl-js', $dir_url . 'assets/bsl_cnf.js', array(), BSL_VERSION, true);
        }
        $wb_ajax_nonce = wp_create_nonce('wp_ajax_wb_baidu_push_url');
        $in_line_js = array();

        $in_line_js['_wb_bsl_ajax_nonce'] = sprintf("'%s'",$wb_ajax_nonce);
        $in_line_js['pd_code'] = sprintf("'%s'",self::$optionName);



        $init_data = array(
            'spider_install'=>1,
            'spider_setup_url'=>admin_url('plugin-install.php?s=Wbolt+Spider+Analyser&tab=search&type=term'),
            'spider_active'=>1,
            'spider_active_url'=>admin_url('plugin-install.php?s=Wbolt+Spider+Analyser&tab=search&type=term'),
            'post_types'=>array(),
            'log_day'=>array(1=>'24小时',3=>'3天',7=>'7天（默认）'),
            'sitemap_exists'=>0,
            'sitemap_url'=>'',
        );
        //check wb spider
        $init_data['spider_install'] = file_exists(WP_CONTENT_DIR.'/plugins/spider-analyser/index.php');
        if($init_data['spider_install']){
            $init_data['spider_active'] = class_exists('WP_Spider_Analyser');
        }

        //post_types
        if($wp_post_types && is_array($wp_post_types))foreach($wp_post_types as $type) {
            if ($type->public) {
                $init_data['post_types'][$type->name] = $type->labels->name;
            }
        }
        $in_line_js['bsl_data'] = json_encode($init_data,JSON_UNESCAPED_UNICODE);

        $in_line_js['bsl_cnf'] = json_encode(WB_BSL_Conf::cnf(null),JSON_UNESCAPED_UNICODE);
        $js = [];
        foreach($in_line_js as $var=>$value){
            $js[] = $var.' = '.$value;
        }
        wp_add_inline_script('wb-bsl-js',' var '.implode(",\n",$js).';','before');

        include BSL_PATH.'/tpl/cnf.php';
    }

    public static function bsl_pro()
    {
        if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
            WB_Core_Asset_Load::load('pro-02');
        }else{
            $dir_url = plugin_dir_url(BSL_BASE_FILE);
            wp_enqueue_script('wb-bsl-js', $dir_url . 'assets/bsl_pro.js', array(), BSL_VERSION, true);
        }
        $wb_ajax_nonce = wp_create_nonce('wp_ajax_wb_baidu_push_url');
        $in_line_js = "var _wb_bsl_ajax_nonce = '$wb_ajax_nonce';";
        $in_line_js .= "var pd_code = '".self::$optionName."';";

        wp_add_inline_script('wb-bsl-js',$in_line_js,'before');

        include BSL_PATH.'/tpl/pro.php';
    }

    public static function admin_settings(){

        global $wpdb;

        $setting_field = WB_BSL_Conf::$optionName;
        $opt_name = WB_BSL_Conf::$optionName;
        $op_sets = WB_BSL_Conf::cnf(null);//get_option( $opt_name );
        $token_valid = get_option('bpu-token-check',0);
        if(!$token_valid && $op_sets['token']){
            //self::testToken();
        }
        $opt = $op_sets;
        $wb_ajax_nonce = wp_create_nonce('wp_ajax_wb_baidu_push_url');
        $in_line_js = "var _wb_bsl_ajax_nonce = '$wb_ajax_nonce';";
        $in_line_js .= "var pd_code = '".self::$optionName."';";
        //var bsl_cnf = {app_active:1,pc_active:0,in_bd_active:1};

        $op_sets['cnf'] = array('wb'=>array());
        $in_line_js .= 'var bsl_cnf='.json_encode($op_sets).';';

        $row = WB_BSL_Stats::day_index();
        $data = array(
            array('name'=>'收录总数','value'=>$row->all_in),
            array('name'=>'近7天收录','value'=>$row->week_in),
            array('name'=>'近30天收录','value'=>$row->month_in),
        );
        $in_line_js .= 'var base_overview = '.json_encode($data).';';

        $baidu_api_url = '';
        if(isset($op_sets['app_id']) && $op_sets['app_id'] && isset($op_sets['app_token']) && $op_sets['app_token']){
            $baidu_api_url = sprintf('http://data.zz.baidu.com/urls?appid=%s&token=%s&type=realtime',$op_sets['app_id'],$op_sets['app_token']);
        }
        $in_line_js .= "var baidu_api_url = '".$baidu_api_url."';";

        $query_times = get_option('wb_bsl_query_times','');
        if(!$query_times || !is_array($query_times)){
            $query_times = array('time'=>0,'times'=>0);
        }

        if($query_times['time']<current_time('U',1)){
            $query_times = array('time'=>current_time('U',1)+86400,'times'=>$query_times['times']);
            $data = WB_BSL_Cron::wb_idx(1);
            if($data && isset($data['query_times'])){
                $query_times['times'] = $data['query_times'];
            }
            update_option('wb_bsl_query_times',$query_times,false);
        }


        $last_check_date = $wpdb->get_var("SELECT max(meta_value) FROM $wpdb->postmeta WHERE meta_key='url_in_baidu_ymd'");
	    $in_line_js .= "var last_check_date = '".$last_check_date."';";
	    $in_line_js .= "var last_query_times = '".$query_times['times']."';";
	    $wb_bsl_check_all = get_option('wb_bsl_check_all',0);// == current_time('Ym') ? 1 : 0;
	    $in_line_js .= 'var baidu_check_all='.($wb_bsl_check_all?1:0).';';

        wp_add_inline_script('bsl-setting-js',$in_line_js,'before');


        //$log_info = self::log_info();

        include  BSL_PATH.'/settings.php';
    }


    public static function log_info($clear = 0)
    {
        $log_info = array();

        if(!get_option('wb_bsl_ver',0)){
            return $log_info;
        }

        $log_file = __DIR__.'/#log/running.log';
        if(!file_exists($log_file)){
            //echo file_get_contents($log_file);
            file_put_contents($log_file,'');
        }
        if($clear){
            file_put_contents($log_file,'');
        }else{
            $file = file($log_file);
            if(count($file)>1000){
                $file = array_slice($file,-1000);
                file_put_contents($log_file,implode('',$file));
            }

            foreach($file as $r){
                $r = trim($r);
                $type = '';
                $time = '';
                if(preg_match_all('#\[([^\]]+)\]#',$r,$m)){
                    $time = $m[1][0];
                    if(isset($m[1][1])){
                        $type = $m[1][1];
                    }
                }


                $msg = $r;
                if($time){
                    $msg = str_replace('['.$time.']','',$msg);
                }
                if($time){
                    $msg = str_replace('['.$type.']','',$msg);
                }

                $msg = trim($msg);

                $log_info[] = array('time'=>$time,'type'=>$type,'msg'=>$msg);
            }
            //rsort($file);
            //$log_info = implode('',$file);
            //$log_info = $file;
        }


        return $log_info;
    }


    public static function actionLinks( $links, $file ) {

        if ( $file != plugin_basename(BSL_BASE_FILE) )
            return $links;

        $settings_link = '<a href="'.admin_url('admin.php?page=wb_bsl_cnf').'">设置</a>';

        array_unshift( $links, $settings_link );

        return $links;
    }

    public static function admin_init(){
        register_setting(  WB_BSL_Conf::$optionName,WB_BSL_Conf::$optionName );
    }


    public static function admin_enqueue_scripts($hook){

        //print_r([urldecode($hook)]);
        if(!preg_match('#wb_bsl#i',$hook)){
            return;
        }
        $dir_url = plugin_dir_url(BSL_BASE_FILE);
        /*if(preg_match('#wb_bsl$#',$hook)){

            if(defined('WB_CORE_ASSETS_LOAD') && class_exists('WB_Core_Asset_Load')){
                WB_Core_Asset_Load::load('setting-02');
            }else{

                wp_enqueue_script('echarts-js', $dir_url . 'assets/echarts.min.js', array(), BSL_VERSION, true);
                wp_enqueue_script('bsl-setting-js', $dir_url . 'assets/bsl_setting.js', array(), BSL_VERSION, true);
            }
        }*/


        wp_enqueue_style('wbs-style-bdsl', $dir_url . 'assets/wb_plugins_bdsl.css', array(),BSL_VERSION);
    }


    public static function plugin_row_meta($links,$file){

        $base = plugin_basename(BSL_BASE_FILE);
        if($file == $base) {
            $links[] = '<a href="https://www.wbolt.com/plugins/bsl?utm_source=bsl_setting&utm_medium=link&utm_campaign=plugins_list" target="_blank">插件主页</a>';
            $links[] = '<a href="https://www.wbolt.com/bsl-plugin-documentation.html?utm_source=bsl_setting&utm_medium=link&utm_campaign=plugins_list" target="_blank">FAQ</a>';
            $links[] = '<a href="https://wordpress.org/support/plugin/baidu-submit-link/" target="_blank">反馈</a>';
        }
        return $links;
    }

    public static function restrict_manage_posts($post_type, $which){
        if(current_user_can('administrator') && get_option('wb_bsl_ver',0)) {

            if(!WB_BSL_Conf::cnf('in_bd_active')){
                return;
            }
            echo '<select name="in_bd"><option value="">百度收录情况</option>';
            foreach (array(1=>'未收录',2=>'已收录') as $k=>$v) {
                $sec = isset($_GET['in_bd']) && $_GET['in_bd'] == $k;
                echo '<option value="' . $k . '" ' . ($sec ? 'selected' : '') . '>' . $v . '</option>';
            }
            echo '</select>';

        }
    }

    public static function post_row_actions($actions, $post){
        if(current_user_can('administrator') && $post->post_status=='publish' && get_option('wb_bsl_ver',0)){
            if(!WB_BSL_Conf::cnf('in_bd_active')){
                return $actions;
            }
            $in_baidu = get_post_meta($post->ID,'url_in_baidu',true);
            if($in_baidu == '1') {
                $action_url2 = '';
                $action_url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd=' . urlencode(get_permalink($post));
                $action_name = '百度已收录';
            }else if($in_baidu == '2'){
                $action_url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd='.urlencode(get_permalink($post));
                $action_name = '<span style="color:#f00">百度未收录</span>';
                $action_url2 = 'https://ziyuan.baidu.com/linksubmit/url?sitename='.urlencode(get_permalink($post));
                $action_name2 = '提交百度';
            }else{
                $action_url2 = '';
                $action_url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd=' . urlencode(get_permalink($post));
                $action_name = '收录检测中';
            }

            $actions['post_in_baidu'] = '<a class="post_in_baidu" target="_blank" href="'.$action_url.'" >'.$action_name.'</a>';
            if($action_url2){
                $actions['post_baidu_tj'] = '<a class="post_baidu_tj" target="_blank" href="'.$action_url2.'" >'.$action_name2.'</a>';
            }

        }
        return $actions;
    }

    public static function admin_parse_query($obj){
        global $wpdb, $current_user;
        if (is_admin()) {
            if ($current_user && $current_user->has_cap('administrator') && isset($_GET['in_bd']) && $_GET['in_bd'] && get_option('wb_bsl_ver',0)) {

                if($_GET['in_bd'] == 2){
                    $obj->query_vars['meta_key'] = 'url_in_baidu';
                    $obj->query_vars['meta_value'] = '1';
                    $obj->query_vars['post_status'] = 'publish';
                }else if($_GET['in_bd']) {
                    $obj->query_vars['post_status'] = 'publish';
                    if(!isset($obj->query_vars['meta_query'])){
                        $obj->query_vars['meta_query'] = array();
                    }
                    $obj->query_vars['meta_query'][] = array(
                        'relation'=>'OR',
                        array('key'=>'url_in_baidu','compare'=>'NOT EXISTS'),
                        array('key'=>'url_in_baidu','value'=>'2'),
                    );
                }
            }
            return;
        }
    }



    public static function plugin_activate(){

        WB_BSL_Conf::setup_db();
    }
    public static function plugin_deactivate(){
        wp_clear_scheduled_hook('baidu_push_url_cron_action_v3');
    }

}