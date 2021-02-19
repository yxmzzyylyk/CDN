<?php


/**
 * 百度接口类
 * Class WB_BSL_Baidu
 */

class WB_BSL_Baidu
{




    public static function scrapy_index($site=null,$from=0,$to=0){


        $siteurl = get_option('siteurl');
        if(!$siteurl){
            $siteurl = home_url();
        }

        $host = parse_url($siteurl,PHP_URL_HOST);

        $defaults = array(
            'timeout' => 3,
            'redirection' => 3,
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
            'sslverify' => FALSE,
        );
        $search_url2 = null;

        $api_url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&rsv_idx=1&tn=baidu&wd=site%3A'.$host;
        if($from && $to){
            $api_url = 'https://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&rsv_idx=1&tn=baidu&wd=site%3A'.$host.'&gpc=stf%3d'.$from.'%2c'.$to.'%7cstftype%3d1';
        }


        $num = 0;
        do{

            //WB_BSL_Utils::run_log('查询百度收录概况，请求接口：');
            WB_BSL_Utils::run_log($api_url,'收录概况');
            $http = wp_remote_get($api_url,$defaults);
            if(is_wp_error($http)){
                WB_BSL_Utils::txt_log('baidu-query-index-not-find-1');

                WB_BSL_Utils::run_log('请求错误，错误【'.$http->get_error_message().'】','收录概况');


                $num = -1;
                break;
            }

            if(200 !== wp_remote_retrieve_response_code($http)){
                WB_BSL_Utils::txt_log('baidu-query-index-not-find-2');
                $num = -1;
                WB_BSL_Utils::run_log('请求错误，响应码【'.wp_remote_retrieve_response_code($http).'】','收录概况');
                break;
            }

            $body = wp_remote_retrieve_body($http);
            if(preg_match('#<title>百度安全验证</title>#is',$body)){
                WB_BSL_Utils::txt_log('baidu-query-index-not-find-3');
                $num = -1;
                WB_BSL_Utils::run_log('请求错误，无法绕过【百度安全验证】','收录概况');
                break;
            }


            if(preg_match('#没有找到#is',$http['body'])){
                WB_BSL_Utils::run_log('请求返回【没有找到数据】','收录概况');
                $num = 0;
                break;
            }

            if(preg_match('#找到相关结果数约([\d,]+)#is',$http['body'],$match)){
                $num = intval(preg_replace('#[^\d]*#','',$match[1]));
                WB_BSL_Utils::run_log('请求返回【找到相关结果数约'.$num.'】','收录概况');
                break;
            }

            if(preg_match('#找到相关结果约([\d,]+)#is',$http['body'],$match)){
                $num = intval(preg_replace('#[^\d]*#','',$match[1]));
                WB_BSL_Utils::run_log('请求返回【找到相关结果约'.$num.'】','收录概况');

                break;
            }

            if(preg_match('#该网站共有.+?([\d,]+).+?个网页#is',$http['body'],$match)){
                $num = intval(preg_replace('#[^\d]*#','',$match[1]));
                WB_BSL_Utils::run_log('请求返回【找到相关结果约'.$num.'】','收录概况');
                break;
            }

            WB_BSL_Utils::run_log('返回错误【数据匹配异常】','收录概况');

        }while(false);

        return $num;

    }



    public static function wb_query($url,$post_title,$in_cron = false)
    {
        do{
            $arg = array(
                'body'=>array('ver'=>get_option('wb_bsl_ver',0),'url'=>$url),
                'headers'=>array('referer'=>home_url()),
                'timeout'   => 3,
                'user-agent' => 'WB-API-BSL-'.BSL_VERSION,
                'sslverify' => false,
            );

            $http = wp_remote_post('https://www.wbolt.com/wb-api/v1/bsl2',$arg);

            if(wp_remote_retrieve_response_code($http)!=200){
                return -1;
                break;
            }

            $body = wp_remote_retrieve_body($http);
            if(!$body){
                return -1;
                break;
            }

            $data = json_decode($body,true);

            if(!$data){
                return -1;
                break;
            }

            if($data['data'] == 1){
                return 1;
            }


            return 0;

        }while(false);
    }

    public static function baidu_query($url,$post_title,$in_cron = false){


        /*$siteurl = get_option('siteurl');
        if(!$siteurl){
            $siteurl = home_url();
        }

        $host = parse_url($siteurl,PHP_URL_HOST);*/

        $defaults = array(
            'timeout' => 3,
            'redirection' => 3,
            'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/57.0.2987.133 Safari/537.36',
            'sslverify' => FALSE,
        );
        $search_url2 = null;

        /*if($host && $post_title){
            $post_title = mb_substr($post_title,0,20);

            $search_url1 = 'http://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd='.urlencode($url);
            $search_url2 = 'https://www.baidu.com/s?q1=&q2='.urlencode($post_title).'&q3=&q4=&gpc=stf&ft=&q5=1&q6='.$host.'&tn=baiduadv';
        }else{
        }*/

        $search_url = 'http://www.baidu.com/s?ie=utf-8&f=8&rsv_bp=1&ch=&tn=baiduerr&bar=&wd='.urlencode($url);
        do{
            WB_BSL_Utils::txt_log($search_url);
            WB_BSL_Utils::txt_log($defaults);
            $http = wp_remote_get($search_url,$defaults);

            if(wp_remote_retrieve_response_code($http)!=200){
                return -1;
                break;
            }
            $body = wp_remote_retrieve_body($http);
            if(!$body){
                return -1;
                break;
            }
            if(preg_match('#提交网址.+?给我们#is',$body)){
                WB_BSL_Utils::txt_log('baidu-query-not-find-1');
                break;
            }
            if(preg_match('#没有找到该URL#is',$body)){
                WB_BSL_Utils::txt_log('baidu-query-not-find-2');
                break;
            }


            if(preg_match('#<title>百度安全验证</title>#is',$body)){
                WB_BSL_Utils::txt_log('baidu-query-not-find-3');
                return -1;
                break;
            }

            if(preg_match('#没有找到与#i',$body)){
                WB_BSL_Utils::txt_log('baidu-query-not-find-4');
                break;
            }
            return 1;

        }while(false);

        /*if($search_url2 && !$in_cron){

            sleep(1);
            $http = wp_remote_get($search_url2,$defaults);

            if(preg_match('#<title>百度安全验证</title>#is',$http['body'])){
                return false;
            }

            if(!is_wp_error($http) && 200 == $http['response']['code'] && !preg_match('#没有找到#is',$http['body'])){
                return true;
            }
        }*/

        return 0;

    }


    /**
     * 百度站长连接主动推送接口
     *
     * @param $urls
     * @param $type
     * @return array
     */
    public static function pc_push($urls,$type,$token= null){

        $apis = array(
            1=>'http://data.zz.baidu.com/urls',
            2=>'http://data.zz.baidu.com/urls',
            3=>'http://data.zz.baidu.com/urls',
        );

        if(!$token){
            $token = WB_BSL_Conf::cnf('token');
        }


        $siteurl = get_option('siteurl');
        $parse = parse_url($siteurl);
        $site = $parse['host'];

        $ret = array(
            'code'=>1,
            'desc'=>'error',
            'data'=>null,
        );

        /*if(!$site){
            $ret['code'] = 10;
            $ret['desc'] = '查询当前域名失败';
            return $ret;
        }*/

        if(!$token){
            $ret['code'] = 10;
            $ret['desc'] = '未设置百度推送token';
            return $ret;
        }
        //
        if(preg_match('#^https?://#i',$token)){
            $api = $token;
        }else{
            $api = $apis[$type].'?site='.$site.'&token='.$token;
        }
        $args = array(
            'timeout'=>10,
            'method'=>'POST',
            'body'=>implode("\n",$urls)
        );
        $http = wp_remote_post($api,$args);
        if(is_wp_error($http)){
            $ret['code'] = 20;
            $ret['desc'] = '接口请求错误,'.$http->get_error_message();
            return $ret;
        }

        if(200 === $http ['response'] ['code']){

            $body = $http ['body'];

            /*
            {"remain":4999998,"success":2,"not_same_site":[],"not_valid":[]}
            */
            $data = json_decode($body,true);
            if(!$data){
                $ret['code'] = 11;
                $ret['desc'] = '接口响应解析出错,响应内容【'.$body.'】';
                return $ret;
            }
            $ret['code'] = 0;
            $ret['desc'] = 'success';
            $ret['data'] = $data;
            return $ret;
        }else{

            if($http['body']){
                /*
                {"error":int,"message":string}
                {"error":400,"message":"site error"} 站点未在站长平台验证
                {"error":400,"message":"empty content"} post内容为空
                {"error":400,"message":"only 2000 urls are allowed once"} 每次最多只能提交2000条链接
                {"error":400,"message":"over quota"} 超过每日配额了，超配额后再提交都是无效的
                {"error":401,"message":"token is not valid"} token错误
                {"error":404,"message":"not found"} 接口地址填写错误
                {"error":500,"message":"internal error, please try later"} 服务器偶然异常，通常重试就会成功
                */
                $lan = array(
                    'site error'=>'站点未在站长平台验证',
                    'empty content'=>'未提交何url',
                    'only 2000 urls are allowed once'=>'每次最多只能提交2000条链接',
                    'over quota'=>'超过每日配额了，超配额后再提交都是无效的',
                    'token is not valid'=>'token错误',
                    'not found'=>'接口地址填写错误',
                    'internal error, please try later'=>'服务器偶然异常，通常重试就会成功',
                );
                $data = json_decode($http['body'],true);
                if(!$data){
                    $ret['code'] = 11;
                    $ret['desc'] = '接口响应解析出错,响应内容【'.$http['body'].'】';
                    return $ret;
                }

                $ret['code'] = 30;
                $ret['desc'] = isset($lan[$data['message']])?$lan[$data['message']]:$data['message'];
                $ret['data'] = $data;
                return $ret;
            }
            $ret['code'] = 12;
            $ret['desc'] = '接口请求出错,响应码【'.$http ['response'] ['code'].'】';
            return $ret;
        }

    }

    public static function daily_push($urls){



        $api = WB_BSL_Conf::cnf('daily_api');







        $ret = array(
            'code'=>1,
            'desc'=>'error',
            'data'=>null,
        );



        if(!$api){
            $ret['code'] = 10;
            $ret['desc'] = '未设置接口调用地址';
            return $ret;
        }



        $args = array(
            'timeout'=>10,
            'method'=>'POST',
            'body'=>implode("\n",$urls)
        );

        //self::log(json_encode($args));

        $http = wp_remote_post($api,$args);
        if(is_wp_error($http)){
            $ret['code'] = 20;
            $ret['desc'] = '接口请求错误,'.$http->get_error_message();
            return $ret;
        }
        if(200 === $http ['response'] ['code']){

            $body = $http ['body'];

            /*
             * {
                    "remain":9,
                    "success":1,
                    "remain_daily":9,
                    "success_daily":1
                }
            {"remain":4999998,"success":2,"not_same_site":[],"not_valid":[]}
            */
            $data = json_decode($body,true);
            if(!$data){
                $ret['code'] = 11;
                $ret['desc'] = '接口响应解析出错,响应内容【'.$body.'】';
                return $ret;
            }
            $ret['code'] = 0;
            $ret['desc'] = 'success';
            $ret['data'] = $data;
            return $ret;
        }else{

            if($http['body']){
                /*
                {"error":int,"message":string}
                {"error":400,"message":"site error"} 站点未在站长平台验证
                {"error":400,"message":"empty content"} post内容为空
                {"error":400,"message":"only 2000 urls are allowed once"} 每次最多只能提交2000条链接
                {"error":400,"message":"over quota"} 超过每日配额了，超配额后再提交都是无效的
                {"error":401,"message":"token is not valid"} token错误
                {"error":404,"message":"not found"} 接口地址填写错误
                {"error":500,"message":"internal error, please try later"} 服务器偶然异常，通常重试就会成功
                */
                $lan = array(
                    'site error'=>'站点未在站长平台验证',
                    'empty content'=>'未提交何url',
                    'only 2000 urls are allowed once'=>'每次最多只能提交2000条链接',
                    'over quota'=>'超过每日配额了，超配额后再提交都是无效的',
                    'token is not valid'=>'token错误',
                    'not found'=>'接口地址填写错误',
                    'internal error, please try later'=>'服务器偶然异常，通常重试就会成功',
                );
                $data = json_decode($http['body'],true);
                if(!$data){
                    $ret['code'] = 11;
                    $ret['desc'] = '接口响应解析出错,响应内容【'.$http['body'].'】';
                    return $ret;
                }

                $ret['code'] = 30;
                $ret['desc'] = isset($lan[$data['message']])?$lan[$data['message']]:$data['message'];
                $ret['data'] = $data;
                return $ret;
            }
            $ret['code'] = 12;
            $ret['desc'] = '接口请求出错,响应码【'.$http ['response'] ['code'].'】';
            return $ret;
        }

    }


    public static function app_push($urls,$type='realtime'){



        $token = WB_BSL_Conf::cnf('app_token');
        $appid = WB_BSL_Conf::cnf('app_id');







        $ret = array(
            'code'=>1,
            'desc'=>'error',
            'data'=>null,
        );



        if(!$appid){
            $ret['code'] = 10;
            $ret['desc'] = '未设置推送appid';
            return $ret;
        }

        if(!$token){
            $ret['code'] = 10;
            $ret['desc'] = '未设置推送token';
            return $ret;
        }

        //http://data.zz.baidu.com/urls?appid=1631599529020724&token=QfmdN41ZYCw7qT3c&type=
        $api = 'http://data.zz.baidu.com/urls?appid='.$appid.'&token='.$token.'&type='.$type;


        //self::log($api);


        $args = array(
            'timeout'=>10,
            'method'=>'POST',
            'body'=>implode("\n",$urls)
        );

        //self::log(json_encode($args));

        $http = wp_remote_post($api,$args);
        if(is_wp_error($http)){
            $ret['code'] = 20;
            $ret['desc'] = '接口请求错误,'.$http->get_error_message();
            return $ret;
        }
        if(200 === $http ['response'] ['code']){

            $body = $http ['body'];

            /*
            {"remain":4999998,"success":2,"not_same_site":[],"not_valid":[]}
            */
            $data = json_decode($body,true);
            if(!$data){
                $ret['code'] = 11;
                $ret['desc'] = '接口响应解析出错,响应内容【'.$body.'】';
                return $ret;
            }
            $ret['code'] = 0;
            $ret['desc'] = 'success';
            $ret['data'] = $data;
            return $ret;
        }else{

            if($http['body']){
                /*
                {"error":int,"message":string}
                {"error":400,"message":"site error"} 站点未在站长平台验证
                {"error":400,"message":"empty content"} post内容为空
                {"error":400,"message":"only 2000 urls are allowed once"} 每次最多只能提交2000条链接
                {"error":400,"message":"over quota"} 超过每日配额了，超配额后再提交都是无效的
                {"error":401,"message":"token is not valid"} token错误
                {"error":404,"message":"not found"} 接口地址填写错误
                {"error":500,"message":"internal error, please try later"} 服务器偶然异常，通常重试就会成功
                */
                $lan = array(
                    'site error'=>'站点未在站长平台验证',
                    'empty content'=>'未提交何url',
                    'only 2000 urls are allowed once'=>'每次最多只能提交2000条链接',
                    'over quota'=>'超过每日配额了，超配额后再提交都是无效的',
                    'token is not valid'=>'token错误',
                    'not found'=>'接口地址填写错误',
                    'internal error, please try later'=>'服务器偶然异常，通常重试就会成功',
                );
                $data = json_decode($http['body'],true);
                if(!$data){
                    $ret['code'] = 11;
                    $ret['desc'] = '接口响应解析出错,响应内容【'.$http['body'].'】';
                    return $ret;
                }

                $ret['code'] = 30;
                $ret['desc'] = isset($lan[$data['message']])?$lan[$data['message']]:$data['message'];
                $ret['data'] = $data;
                return $ret;
            }
            $ret['code'] = 12;
            $ret['desc'] = '接口请求出错,响应码【'.$http ['response'] ['code'].'】';
            return $ret;
        }

    }
}