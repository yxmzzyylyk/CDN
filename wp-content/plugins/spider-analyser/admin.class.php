<?php

if(!defined('ABSPATH')){
    return;
}

class WP_Spider_Analyser_Admin
{
    public static $option = 'wp_spider_analyser_option';

    public static function init(){
//    	add_action( 'admin_menu', array(__CLASS__,'adminMenu') );
    }

    public static function adminMenu(){
        add_submenu_page('wp_spider_analyser','插件设置', '插件设置', 'administrator','wp_spider_analyser_conf' , array(__CLASS__,'wp_spider_analyser_conf'));
    }
    public static function spider_types(){

        $types = array(
            'Feed爬取类',
            'SEO/SEM类',
            '工具类',
            '搜索引擎',
            '漏洞扫描类',
            '病毒扫描类',
            '网站截图类',
            '网站爬虫类',
            '网站监控',
            '速度测试类',
            '链接检测类',
            '其他',
        );
        return apply_filters('spider_analyser_url_types',$types);
    }
    public static function url_types()
    {
        $types =  array(
            'index'=>'首页',
            'post'=>'文章页',
            'page'=>'独立页',
            'category'=>'分类页',
            'tag'=>'标签页',
            'search'=>'搜索页',
            'author'=>'作者页',
            'feed'=>'Feed',
            'sitemap'=>'SiteMap',
            'api'=>'API',
            'other'=>'其他'
        );
        return apply_filters('spider_analyser_url_types',$types);
    }
    public static function cnf()
    {

        $cnf = get_option(self::$option,array());
        foreach (array('log_keep'=>3,'forbid'=>array(),'user_define'=>array(),'user_rule'=>array(),'extral_rule'=>array()) as $key=>$val){
            if(!isset($cnf[$key])){
                $cnf[$key] = $val;
            }
        }
        $url_types = self::url_types();
        if($url_types)foreach($url_types as $k=>$v){
            if(!isset($cnf['extral_rule'][$k])){
                $cnf['extral_rule'][$k] = '';
            }

        }

        //,'spider'=>array()

        /*if(!isset($cnf['spider'])){
            $cnf['spider'] = array_values(WP_Spider_Analyser::spider_info());
        }*/
        return $cnf;
    }

    public static function update_cnf()
    {
        $opt_data = $_POST['opt'];
        $spider = $_POST['type'];

        //print_r($spider);
        //print_r($opt_data);exit();

        //$spider_info
        if(is_array($spider)){
            $spider_info = array();
            foreach($spider as $r){
                $spider_info[$r['name']] = $r;
            }
            if($spider_info){
                $info = array('expired'=>current_time('U',1) + 1 * HOUR_IN_SECONDS,'data'=>$spider_info);

                update_option('wb_spider_info',$info,false);
            }

        }

        /*$spider_info = WP_Spider_Analyser::spider_info();
        foreach($spider as $k=>$r){
            if(isset($spider_info[$r['name']])){
                $row = $spider_info[$r['name']];
                $row['name'] = $r['name'];
                $row['bot_type'] = $r['bot_type'];
                $spider_info[$r['name']] = $row;
            }
        }*/
        if(!is_array($opt_data['user_define'])){
            $opt_data['user_define'] = array();
        }
        $user_define = array();
        foreach($opt_data['user_define'] as $k=>$v){
            $v = trim($v);
            if(!$v){
                continue;
            }
            $user_define[] = $v;
        }
        $opt_data['user_define'] = $user_define;

        if(!is_array($opt_data['user_rule'])){
            $opt_data['user_rule'] = array();
        }
        $user_rule = array();
        foreach($opt_data['user_rule'] as $k=>$v){
            $name = trim($v['name']);
            if(!$name){
                continue;
            }
            $rule = trim($v['rule']);
            if(!$rule){
                continue;
            }
            $user_rule[] = array('name' => $name,'rule' => $rule);
        }
        $opt_data['user_rule'] = $user_rule;


        update_option( self::$option, $opt_data );

    }

    public static function wp_spider_analyser_conf(){

        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }


        global $wpdb;

        $t = $wpdb->prefix.'wb_spider_log';
        //所有蜘蛛
        $spider = $wpdb->get_col("SELECT DISTINCT spider FROM $t");
        //近7天访问量
        $spider_recent_num = $wpdb->get_var("SELECT COUNT(1) num FROM $t WHERE visit_date>DATE_ADD(NOW(),INTERVAL -30 DAY)");
        $spider_recent_num = max($spider_recent_num,1);
        $spider_recent = $wpdb->get_results("SELECT ROUND(COUNT(1) * 100 / $spider_recent_num ,2) AS rate, spider FROM $t WHERE visit_date>DATE_ADD(NOW(),INTERVAL -30 DAY) GROUP BY spider ORDER BY rate DESC");
        //print_r($spider_recent);


        //所有蜘蛛
        $spider_info = WP_Spider_Analyser::spider_info();
        //print_r($spider_info);
        $spider_type = array();
        $cnf = self::cnf();
        $cnf['spider'] = array();

        $v_rate = array();
        if($spider_recent)foreach($spider_recent as $v){
            $v_rate[$v->spider] = $v->rate;
            $rate = $v->rate;
            $one = array('name'=>$v->spider,'bot_type'=>'','bot_url'=>'');
            if(isset($spider_info[$v->spider])){
                $one= $spider_info[$v->spider];
                if(!isset($one['bot_type'])){
                    $one['bot_type'] = '';
                }
            }
            if($one['bot_type'] == '未分类'){
                $one['bot_type'] = '';
            }
            $one['rate'] = $rate;
            $one['status'] = 1;
            if($cnf['forbid'] && in_array($one['name'],$cnf['forbid'])){
                $one['status'] = 0;
            }
            $spider_type[] = $one ;
        }

        foreach($spider as $k=>$v){
            if(isset($v_rate[$v])){
                continue;
            }
            $v_rate[$v] = 0;
            $rate = 0;
            $one = array('name'=>$v,'bot_type'=>'','bot_url'=>'');
            if(isset($spider_info[$v])){
                $one= $spider_info[$v];
                if(!isset($one['bot_type'])){
                    $one['bot_type'] = '';
                }
            }
            if($one['bot_type'] == '未分类'){
                $one['bot_type'] = '';
            }
            $one['rate'] = $rate;
            $one['status'] = 1;
            if($cnf['forbid'] && in_array($one['name'],$cnf['forbid'])){
                $one['status'] = 0;
            }
            $spider_type[] = $one ;
        }

        foreach ($spider_info as $r){
            if(isset($v_rate[$r['name']])){
                continue;
            }
            $rate = 0;
            $one = $r;
            if(!isset($one['bot_type'])){
                $one['bot_type'] = '';
            }
            if($one['bot_type'] == '未分类'){
                $one['bot_type'] = '';
            }
            $one['rate'] = $rate;
            $one['status'] = 1;
            if($cnf['forbid'] && in_array($one['name'],$cnf['forbid'])){
                $one['status'] = 0;
            }
            $spider_type[] = $one ;
        }


        $spider_data = array(
            'all'=>$spider,
            'type'=>$spider_type,
            'spider_type'=>self::spider_types(),
            'url_type'=>self::url_types(),
        );

        $res=array();
        $res['opt'] = $cnf;
        $res['spider'] = $spider_data;

        return $res;


        /*1. 增加所有蜘蛛类型列表 opt.bot_type_items
        2. Opt.spider 按最近7天占比降序输出；增加状态字段（记录/忽略）*/
	    //$inline_js = 'var spider_data = {"all":["360Spider","Adsbot","AdsTxtCrawler","AhrefsBot","aiHitBot","Amazon-Advertising-ad-standards-bot","Applebot","Baiduspider","bingbot","BLEXBot","bot","bots","Bytespider","CCBot","Checkbot","CloudServerMarketSpider","coccocbot","Cocolyzebot","contxbot","crawler","DF Bot","DingTalkBot","domainsbot","DomainStatsBot","DotBot","DuckDuckBot","DuckDuckGo-Favicons-Bot","Facebot","feedbot","Googlebot","GrapeshotCrawler","ICC-Crawler","Internet-structure-research-project-bot","Jooblebot","Konturbot","LightspeedSystemsCrawler","Linespider","linkdexbot","MagiBot","MJ12bot","MojeekBot","Nimbostratus-Bot","oBot","PetalBot","pimeyes.com crawler","PlurkBot","Qwantify","RedirectBot","robot","RSSingBot","SabsimBot","SemrushBot","SeznamBot","SMTBot","sogou spider","spider","SurdotlyBot","TelegramBot","TweetmemeBot","Twitterbot","web spider","www.atspider","www.killerrobots","www.lbotu","www.scispider","www.spiderstroll","yacybot","Yahoo!","YandexBot","YandexMobileBot","YisouSpider","YisouSpider123","zhanbot"],"info":{"360Spider":{"name":"360Spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.haosou.com\/help\/help_3_2.html"},"Adsbot":{"name":"Adsbot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"AhrefsBot":{"name":"AhrefsBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"https:\/\/ahrefs.com\/robot"},"Applebot":{"name":"Applebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.apple.com\/go\/applebot"},"Baiduspider":{"name":"Baiduspider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.baidu.com\/search\/spider.htm"},"bingbot":{"name":"bingbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.bing.com\/webmaster\/help\/which-crawlers-does-bing-use-8c184ec0"},"BLEXBot":{"name":"BLEXBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/webmeup-crawler.com\/"},"bots":{"name":"bots","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Bytespider":{"name":"Bytespider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"CCBot":{"name":"CCBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/commoncrawl.org\/faq\/"},"Checkbot":{"name":"Checkbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"coccocbot":{"name":"coccocbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/help.coccoc.com\/searchengine"},"Cocolyzebot":{"name":"Cocolyzebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DingTalkBot":{"name":"DingTalkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/ding-doc.dingtalk.com\/doc#\/faquestions\/ftpfeu"},"domainsbot":{"name":"domainsbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DotBot":{"name":"DotBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.opensiteexplorer.org\/dotbot"},"DuckDuckBot":{"name":"DuckDuckBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/duckduckgo.com\/duckduckbot"},"Facebot":{"name":"Facebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"feedbot":{"name":"feedbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Googlebot":{"name":"Googlebot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.google.com\/bot.html"},"Jooblebot":{"name":"Jooblebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/jooble.org\/jooblebot"},"Konturbot":{"name":"Konturbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"linkdexbot":{"name":"linkdexbot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.linkdex.com\/bots\/"},"MagiBot":{"name":"MagiBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"MJ12bot":{"name":"MJ12bot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/mj12bot.com\/"},"MojeekBot":{"name":"MojeekBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/www.mojeek.com\/bot.html"},"oBot":{"name":"oBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.xforce-security.com\/crawler\/"},"PetalBot":{"name":"PetalBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/aspiegel.com\/petalbot"},"RedirectBot":{"name":"RedirectBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"robot":{"name":"robot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"RSSingBot":{"name":"RSSingBot","bot_type":"Feed\u722c\u53d6\u7c7b","bot_url":null},"SemrushBot":{"name":"SemrushBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.semrush.com\/bot.html"},"SeznamBot":{"name":"SeznamBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/napoveda.seznam.cz\/en\/seznambot-intro\/"},"SMTBot":{"name":"SMTBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.similartech.com\/smtbot"},"spider":{"name":"spider","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"SurdotlyBot":{"name":"SurdotlyBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/sur.ly\/bot.html"},"YandexBot":{"name":"YandexBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yandex.com\/bots"},"YandexMobileBot":{"name":"YandexMobileBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"YisouSpider":{"name":"YisouSpider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},"YisouSpider123":{"name":"YisouSpider123","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"zhanbot":{"name":"zhanbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"sogou spider":{"name":"sogou spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.sogou.com\/docs\/help\/webmasters.htm#07"},"Twitterbot":{"name":"Twitterbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"GrapeshotCrawler":{"name":"GrapeshotCrawler","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.grapeshot.co.uk\/crawler.php"},"yacybot":{"name":"yacybot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yacy.net\/bot.html"},"Nimbostratus-Bot":{"name":"Nimbostratus-Bot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DF Bot":{"name":"DF Bot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"Qwantify":{"name":"Qwantify","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/help.qwant.com\/bot"},"Linespider":{"name":"Linespider","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/lin.ee\/4dwXkTH"},"TweetmemeBot":{"name":"TweetmemeBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/datasift.com\/bot.html"},"AdsTxtCrawler":{"name":"AdsTxtCrawler","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"TelegramBot":{"name":"TelegramBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/telegram.org\/blog\/bot-revolution"},"DomainStatsBot":{"name":"DomainStatsBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/domainstats.com\/pages\/our-bot"},"aiHitBot":{"name":"aiHitBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"PlurkBot":{"name":"PlurkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"web spider":{"name":"web spider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DuckDuckGo-Favicons-Bot":{"name":"DuckDuckGo-Favicons-Bot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},"crawler":{"name":"crawler","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"pimeyes.com crawler":{"name":"pimeyes.com crawler","bot_type":null,"bot_url":null},"ICC-Crawler":{"name":"ICC-Crawler","bot_type":null,"bot_url":null},"LightspeedSystemsCrawler":{"name":"LightspeedSystemsCrawler","bot_type":null,"bot_url":null},"www.spiderstroll":{"name":"www.spiderstroll","bot_type":null,"bot_url":null},"www.scispider":{"name":"www.scispider","bot_type":null,"bot_url":null},"Internet-structure-research-project-bot":{"name":"Internet-structure-research-project-bot","bot_type":null,"bot_url":null},"CloudServerMarketSpider":{"name":"CloudServerMarketSpider","bot_type":null,"bot_url":null},"SabsimBot":{"name":"SabsimBot","bot_type":null,"bot_url":null},"contxbot":{"name":"contxbot","bot_type":null,"bot_url":null},"Amazon-Advertising-ad-standards-bot":{"name":"Amazon-Advertising-ad-standards-bot","bot_type":null,"bot_url":null},"www.killerrobots":{"name":"www.killerrobots","bot_type":null,"bot_url":null}},"type":{"360Spider":{"name":"360Spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.haosou.com\/help\/help_3_2.html"},"Adsbot":{"name":"Adsbot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"AdsTxtCrawler":{"name":"AdsTxtCrawler","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"AhrefsBot":{"name":"AhrefsBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"https:\/\/ahrefs.com\/robot"},"aiHitBot":{"name":"aiHitBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Amazon-Advertising-ad-standards-bot":{"name":"Amazon-Advertising-ad-standards-bot","bot_type":null,"bot_url":null},"Applebot":{"name":"Applebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.apple.com\/go\/applebot"},"Baiduspider":{"name":"Baiduspider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.baidu.com\/search\/spider.htm"},"bingbot":{"name":"bingbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.bing.com\/webmaster\/help\/which-crawlers-does-bing-use-8c184ec0"},"BLEXBot":{"name":"BLEXBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/webmeup-crawler.com\/"},"bot":{"name":"bot","bot_type":"","bot_url":""},"bots":{"name":"bots","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Bytespider":{"name":"Bytespider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"CCBot":{"name":"CCBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/commoncrawl.org\/faq\/"},"Checkbot":{"name":"Checkbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"CloudServerMarketSpider":{"name":"CloudServerMarketSpider","bot_type":null,"bot_url":null},"coccocbot":{"name":"coccocbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/help.coccoc.com\/searchengine"},"Cocolyzebot":{"name":"Cocolyzebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"contxbot":{"name":"contxbot","bot_type":null,"bot_url":null},"crawler":{"name":"crawler","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DF Bot":{"name":"DF Bot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"DingTalkBot":{"name":"DingTalkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/ding-doc.dingtalk.com\/doc#\/faquestions\/ftpfeu"},"domainsbot":{"name":"domainsbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"DomainStatsBot":{"name":"DomainStatsBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/domainstats.com\/pages\/our-bot"},"DotBot":{"name":"DotBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.opensiteexplorer.org\/dotbot"},"DuckDuckBot":{"name":"DuckDuckBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/duckduckgo.com\/duckduckbot"},"DuckDuckGo-Favicons-Bot":{"name":"DuckDuckGo-Favicons-Bot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},"Facebot":{"name":"Facebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"feedbot":{"name":"feedbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Googlebot":{"name":"Googlebot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.google.com\/bot.html"},"GrapeshotCrawler":{"name":"GrapeshotCrawler","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.grapeshot.co.uk\/crawler.php"},"ICC-Crawler":{"name":"ICC-Crawler","bot_type":null,"bot_url":null},"Internet-structure-research-project-bot":{"name":"Internet-structure-research-project-bot","bot_type":null,"bot_url":null},"Jooblebot":{"name":"Jooblebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/jooble.org\/jooblebot"},"Konturbot":{"name":"Konturbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"LightspeedSystemsCrawler":{"name":"LightspeedSystemsCrawler","bot_type":null,"bot_url":null},"Linespider":{"name":"Linespider","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/lin.ee\/4dwXkTH"},"linkdexbot":{"name":"linkdexbot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.linkdex.com\/bots\/"},"MagiBot":{"name":"MagiBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"MJ12bot":{"name":"MJ12bot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/mj12bot.com\/"},"MojeekBot":{"name":"MojeekBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/www.mojeek.com\/bot.html"},"Nimbostratus-Bot":{"name":"Nimbostratus-Bot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"oBot":{"name":"oBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.xforce-security.com\/crawler\/"},"PetalBot":{"name":"PetalBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/aspiegel.com\/petalbot"},"pimeyes.com crawler":{"name":"pimeyes.com crawler","bot_type":null,"bot_url":null},"PlurkBot":{"name":"PlurkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"Qwantify":{"name":"Qwantify","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/help.qwant.com\/bot"},"RedirectBot":{"name":"RedirectBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"robot":{"name":"robot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"RSSingBot":{"name":"RSSingBot","bot_type":"Feed\u722c\u53d6\u7c7b","bot_url":null},"SabsimBot":{"name":"SabsimBot","bot_type":null,"bot_url":null},"SemrushBot":{"name":"SemrushBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.semrush.com\/bot.html"},"SeznamBot":{"name":"SeznamBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/napoveda.seznam.cz\/en\/seznambot-intro\/"},"SMTBot":{"name":"SMTBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.similartech.com\/smtbot"},"sogou spider":{"name":"sogou spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.sogou.com\/docs\/help\/webmasters.htm#07"},"spider":{"name":"spider","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},"SurdotlyBot":{"name":"SurdotlyBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/sur.ly\/bot.html"},"TelegramBot":{"name":"TelegramBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/telegram.org\/blog\/bot-revolution"},"TweetmemeBot":{"name":"TweetmemeBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/datasift.com\/bot.html"},"Twitterbot":{"name":"Twitterbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"web spider":{"name":"web spider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"www.atspider":{"name":"www.atspider","bot_type":"","bot_url":""},"www.killerrobots":{"name":"www.killerrobots","bot_type":null,"bot_url":null},"www.lbotu":{"name":"www.lbotu","bot_type":"","bot_url":""},"www.scispider":{"name":"www.scispider","bot_type":null,"bot_url":null},"www.spiderstroll":{"name":"www.spiderstroll","bot_type":null,"bot_url":null},"yacybot":{"name":"yacybot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yacy.net\/bot.html"},"Yahoo!":{"name":"Yahoo!","bot_type":"","bot_url":""},"YandexBot":{"name":"YandexBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yandex.com\/bots"},"YandexMobileBot":{"name":"YandexMobileBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"YisouSpider":{"name":"YisouSpider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},"YisouSpider123":{"name":"YisouSpider123","bot_type":"\u672a\u5206\u7c7b","bot_url":null},"zhanbot":{"name":"zhanbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null}},"recent":[{"rate":"1.03","spider":"360Spider"},{"rate":"3.56","spider":"AhrefsBot"},{"rate":"0.34","spider":"Applebot"},{"rate":"27.86","spider":"Baiduspider"},{"rate":"14.27","spider":"bingbot"},{"rate":"0.51","spider":"bot"},{"rate":"1.29","spider":"Bytespider"},{"rate":"0.79","spider":"CCBot"},{"rate":"0.05","spider":"coccocbot"},{"rate":"0.08","spider":"DuckDuckGo-Favicons-Bot"},{"rate":"0.18","spider":"feedbot"},{"rate":"17.66","spider":"Googlebot"},{"rate":"0.04","spider":"Linespider"},{"rate":"11.28","spider":"MJ12bot"},{"rate":"0.01","spider":"MojeekBot"},{"rate":"0.01","spider":"Nimbostratus-Bot"},{"rate":"1.15","spider":"PetalBot"},{"rate":"0.01","spider":"Qwantify"},{"rate":"0.01","spider":"robot"},{"rate":"0.04","spider":"RSSingBot"},{"rate":"4.15","spider":"SemrushBot"},{"rate":"0.24","spider":"SeznamBot"},{"rate":"0.03","spider":"spider"},{"rate":"0.01","spider":"TelegramBot"},{"rate":"0.02","spider":"Twitterbot"},{"rate":"0.34","spider":"www.lbotu"},{"rate":"0.11","spider":"www.spiderstroll"},{"rate":"0.01","spider":"Yahoo!"},{"rate":"0.43","spider":"YandexBot"},{"rate":"14.52","spider":"YisouSpider"}],"url_type":{"index":"\u9996\u9875","post":"\u6587\u7ae0\u9875","page":"\u72ec\u7acb\u9875","category":"\u5206\u7c7b\u9875","tag":"\u6807\u7b7e\u9875","search":"\u641c\u7d22\u9875","author":"\u4f5c\u8005\u9875","feed":"Feed","sitemap":"SiteMap","api":"API","other":"\u5176\u4ed6"}}; var spider_option={"bot_type_items":["搜索引擎","网站爬虫类","SEO/SEM类","未分类"], "log_keep":3,"forbid":[],"user_define":[],"user_rule":[],"spider":[{"name":"360Spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.haosou.com\/help\/help_3_2.html"},{"name":"Adsbot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"AhrefsBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"https:\/\/ahrefs.com\/robot"},{"name":"Applebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.apple.com\/go\/applebot"},{"name":"Baiduspider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.baidu.com\/search\/spider.htm"},{"name":"bingbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.bing.com\/webmaster\/help\/which-crawlers-does-bing-use-8c184ec0"},{"name":"BLEXBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/webmeup-crawler.com\/"},{"name":"bots","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Bytespider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"CCBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/commoncrawl.org\/faq\/"},{"name":"Checkbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"coccocbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/help.coccoc.com\/searchengine"},{"name":"Cocolyzebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DingTalkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/ding-doc.dingtalk.com\/doc#\/faquestions\/ftpfeu"},{"name":"domainsbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DotBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.opensiteexplorer.org\/dotbot"},{"name":"DuckDuckBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/duckduckgo.com\/duckduckbot"},{"name":"Facebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"feedbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Googlebot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.google.com\/bot.html"},{"name":"Jooblebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/jooble.org\/jooblebot"},{"name":"Konturbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"linkdexbot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.linkdex.com\/bots\/"},{"name":"MagiBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"MJ12bot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/mj12bot.com\/"},{"name":"MojeekBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/www.mojeek.com\/bot.html"},{"name":"oBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.xforce-security.com\/crawler\/"},{"name":"PetalBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/aspiegel.com\/petalbot"},{"name":"RedirectBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"robot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"RSSingBot","bot_type":"Feed\u722c\u53d6\u7c7b","bot_url":null},{"name":"SemrushBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.semrush.com\/bot.html"},{"name":"SeznamBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/napoveda.seznam.cz\/en\/seznambot-intro\/"},{"name":"SMTBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.similartech.com\/smtbot"},{"name":"spider","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"SurdotlyBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/sur.ly\/bot.html"},{"name":"YandexBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yandex.com\/bots"},{"name":"YandexMobileBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"YisouSpider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},{"name":"YisouSpider123","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"zhanbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"sogou spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.sogou.com\/docs\/help\/webmasters.htm#07"},{"name":"Twitterbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"GrapeshotCrawler","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.grapeshot.co.uk\/crawler.php"},{"name":"yacybot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yacy.net\/bot.html"},{"name":"Nimbostratus-Bot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DF Bot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"Qwantify","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/help.qwant.com\/bot"},{"name":"Linespider","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/lin.ee\/4dwXkTH"},{"name":"TweetmemeBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/datasift.com\/bot.html"},{"name":"AdsTxtCrawler","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"TelegramBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/telegram.org\/blog\/bot-revolution"},{"name":"DomainStatsBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/domainstats.com\/pages\/our-bot"},{"name":"aiHitBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"PlurkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"web spider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DuckDuckGo-Favicons-Bot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},{"name":"crawler","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"pimeyes.com crawler","bot_type":null,"bot_url":null},{"name":"ICC-Crawler","bot_type":null,"bot_url":null},{"name":"LightspeedSystemsCrawler","bot_type":null,"bot_url":null},{"name":"www.spiderstroll","bot_type":null,"bot_url":null},{"name":"www.scispider","bot_type":null,"bot_url":null},{"name":"Internet-structure-research-project-bot","bot_type":null,"bot_url":null},{"name":"CloudServerMarketSpider","bot_type":null,"bot_url":null},{"name":"SabsimBot","bot_type":null,"bot_url":null},{"name":"contxbot","bot_type":null,"bot_url":null},{"name":"Amazon-Advertising-ad-standards-bot","bot_type":null,"bot_url":null},{"name":"www.killerrobots","bot_type":null,"bot_url":null},{"name":"360Spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.haosou.com\/help\/help_3_2.html"},{"name":"Adsbot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"AdsTxtCrawler","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"AhrefsBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"https:\/\/ahrefs.com\/robot"},{"name":"aiHitBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Amazon-Advertising-ad-standards-bot","bot_type":null,"bot_url":null},{"name":"Applebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.apple.com\/go\/applebot"},{"name":"Baiduspider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.baidu.com\/search\/spider.htm"},{"name":"bingbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.bing.com\/webmaster\/help\/which-crawlers-does-bing-use-8c184ec0"},{"name":"BLEXBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/webmeup-crawler.com\/"},{"name":"bots","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Bytespider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"CCBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/commoncrawl.org\/faq\/"},{"name":"Checkbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"CloudServerMarketSpider","bot_type":null,"bot_url":null},{"name":"coccocbot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/help.coccoc.com\/searchengine"},{"name":"Cocolyzebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"contxbot","bot_type":null,"bot_url":null},{"name":"crawler","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DF Bot","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"DingTalkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/ding-doc.dingtalk.com\/doc#\/faquestions\/ftpfeu"},{"name":"domainsbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"DomainStatsBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/domainstats.com\/pages\/our-bot"},{"name":"DotBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.opensiteexplorer.org\/dotbot"},{"name":"DuckDuckBot","bot_type":"\u5de5\u5177\u7c7b","bot_url":"https:\/\/duckduckgo.com\/duckduckbot"},{"name":"DuckDuckGo-Favicons-Bot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},{"name":"Facebot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"feedbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Googlebot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.google.com\/bot.html"},{"name":"GrapeshotCrawler","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.grapeshot.co.uk\/crawler.php"},{"name":"ICC-Crawler","bot_type":null,"bot_url":null},{"name":"Internet-structure-research-project-bot","bot_type":null,"bot_url":null},{"name":"Jooblebot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/jooble.org\/jooblebot"},{"name":"Konturbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"LightspeedSystemsCrawler","bot_type":null,"bot_url":null},{"name":"Linespider","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/lin.ee\/4dwXkTH"},{"name":"linkdexbot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.linkdex.com\/bots\/"},{"name":"MagiBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"MJ12bot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/mj12bot.com\/"},{"name":"MojeekBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/www.mojeek.com\/bot.html"},{"name":"Nimbostratus-Bot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"oBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/www.xforce-security.com\/crawler\/"},{"name":"PetalBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/aspiegel.com\/petalbot"},{"name":"pimeyes.com crawler","bot_type":null,"bot_url":null},{"name":"PlurkBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"Qwantify","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"https:\/\/help.qwant.com\/bot"},{"name":"RedirectBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"robot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"RSSingBot","bot_type":"Feed\u722c\u53d6\u7c7b","bot_url":null},{"name":"SabsimBot","bot_type":null,"bot_url":null},{"name":"SemrushBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.semrush.com\/bot.html"},{"name":"SeznamBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/napoveda.seznam.cz\/en\/seznambot-intro\/"},{"name":"SMTBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/www.similartech.com\/smtbot"},{"name":"sogou spider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/www.sogou.com\/docs\/help\/webmasters.htm#07"},{"name":"spider","bot_type":"\u7f51\u7ad9\u722c\u866b\u7c7b","bot_url":null},{"name":"SurdotlyBot","bot_type":"SEO\/SEM\u7c7b","bot_url":"http:\/\/sur.ly\/bot.html"},{"name":"TelegramBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"https:\/\/telegram.org\/blog\/bot-revolution"},{"name":"TweetmemeBot","bot_type":"\u672a\u5206\u7c7b","bot_url":"http:\/\/datasift.com\/bot.html"},{"name":"Twitterbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"web spider","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"www.killerrobots","bot_type":null,"bot_url":null},{"name":"www.scispider","bot_type":null,"bot_url":null},{"name":"www.spiderstroll","bot_type":null,"bot_url":null},{"name":"yacybot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yacy.net\/bot.html"},{"name":"YandexBot","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":"http:\/\/yandex.com\/bots"},{"name":"YandexMobileBot","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"YisouSpider","bot_type":"\u641c\u7d22\u5f15\u64ce","bot_url":null},{"name":"YisouSpider123","bot_type":"\u672a\u5206\u7c7b","bot_url":null},{"name":"zhanbot","bot_type":"\u672a\u5206\u7c7b","bot_url":null}]};';
    }
}