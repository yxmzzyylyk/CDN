<?php
/**
 * This was contained in an addon until version 1.0.0 when it was rolled into
 * core.
 *
 * @package    WBOLT
 * @author     WBOLT
 * @since      3.4.0
 * @license    GPL-2.0+
 * @copyright  Copyright (c) 2019, WBOLT
 */


if(!defined('ABSPATH')){
    return;
}


$pd_title = '搜索推送管理';
$pd_version = BSL_VERSION;
$pd_code = 'bsl-setting';
$pd_index_url = 'https://www.wbolt.com/plugins/bsl-pro';
$pd_doc_url = 'https://www.wbolt.com/bsl-plugin-documentation.html';


$spider_install = file_exists(WP_CONTENT_DIR.'/plugins/spider-analyser/index.php');
if($spider_install){
    $spider_active = class_exists('WP_Spider_Analyser');
}
?>

<?php/**<div class="notice-info notice is-dismissible" id="J_wbBslNotice">
 *     <p>通知：</p>
 *     <p>（1）百度已于12月11日临时下线自动推送，通过“插件设置-推送API”可关闭百度自动推送。</p>
 * 	<p>（2）插件升级至v3.4.11，结构做了较大的调整，部分插件设置开关需站长重新启用，请留意。</p>
 * </div>
 */?>

<div class="v-wp" id="optionsframework-wrap" data-wba-source="<?php echo $pd_code; ?>" v-cloak>
    <div id="version_tips" v-if="new_ver">
        <div class="update-message notice inline notice-warning notice-alt">

            <p>当前<?php echo $pd_title;?>有新版本可用. <a href="<?php echo $pd_index_url; ?>" data-wba-campaign="notice-bar#J_updateRecordsSection" target="_blank">查看版本<span class="ver">{{new_ver}}</span> 详情</a>
                或 <a href="<?php echo admin_url('/plugins.php?plugin_status=upgrade');?>" class="update-link" aria-label="现在更新<?php echo $pd_title;?>">现在更新</a>.
            </p>

        </div>
    </div>


<form action="options.php" method="post" autocomplete="off" class="wrap wbs-wrap">

    <div class="wbs-header">
        <svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg>
        <span>WBOLT</span>
        <strong><?php echo $pd_title; ?><i class="tag-pro" v-if="is_pro">PRO版</i><i class="tag-pro free" v-if="!is_pro">Free版</i></strong>

        <div class="links">
            <a class="wb-btn" href="<?php echo $pd_index_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-plugins"><use xlink:href="#sico-plugins"></use></svg>
                <span>插件主页</span>
            </a>
            <a class="wb-btn" href="<?php echo $pd_doc_url; ?>" data-wba-campaign="title-bar" target="_blank">
                <svg class="wb-icon sico-doc"><use xlink:href="#sico-doc"></use></svg>
                <span>说明文档</span>
            </a>
        </div>
    </div>

    <div class="wbs-main">
        <div class="wbs-aside wbs-aside-bsl">
            <ul class="wbs-tabs wbs-menu">
                <li class="tab-item" :class="{current: cur_section=='overview'}">
                    <a class="lv1" @click="switchMenu('overview')"><svg class="wb-icon sico-data"><use xlink:href="#sico-data"></use></svg><span>数据统计</span></a>
                    <div class="sub-menu">
                        <a :class="{current: cur_section=='overview'}" @click="switchMenu('overview')"><span>整站收录统计</span></a>
                        <a :class="{current: ['collection','push_daily'].indexOf(cur_section)>-1}" @click="switchMenu('collection')"><span>百度推送统计</span></a>
                        <a v-if="bing_active" :class="{current: cur_section=='bing'}" @click="switchMenu('bing')"><span>Bing推送统计</span></a>
                        <a :class="{current: cur_section=='collection_list'}" @click="switchMenu('collection_list')"><span>文章收录清单</span><i class="tag-pro" v-if="!is_pro">Pro</i></a>
                        <a v-if="cnf.check_404" :class="{current: cur_section=='sp_404_url'}" @click="switchMenu('sp_404_url')"><span>死链提交清单</span></a>

                    </div>
                </li>
                <li class="tab-item" :class="{current: cur_section=='log_takepush'}">
                    <a class="lv1" @click="switchMenu('log_takepush')"><svg class="wb-icon sico-log"><use xlink:href="#sico-log"></use></svg><span>相关日志</span></a>
                    <div class="sub-menu">
                        <a :class="{current: ['log_takepush','log_dailypush'].indexOf(cur_section)>-1}" @click="switchMenu('log_takepush')"><span>百度推送日志</span></a>

                        <a v-if="bing_active" :class="{current: ['bing_manual','bing_auto'].indexOf(cur_section)>-1}" @click="switchMenu('bing_manual')"><span>Bing推送日志</span></a>
                        <a :class="{current: cur_section=='log_setting'}" @click="switchMenu('log_setting')"><span>插件执行日志</span><i class="tag-pro" v-if="!is_pro">Pro</i></a>
                    </div>
                </li>
                <li class="tab-item" :class="{current: cur_section=='setting'}">
                    <a class="lv1" @click="switchMenu('setting')"><svg class="wb-icon sico-setting"><use xlink:href="#sico-setting"></use></svg><span>插件设置</span></a>
                    <div class="sub-menu">
                        <a @click="cur_section='setting'" href="#settingType"><span>推送文章类型</span></a>
                        <a @click="cur_section='setting'" href="#settingSearchBD"><span>百度推送设置</span></a>
                        <a @click="cur_section='setting'" href="#setting404url"><span>死链检测设置</span></a>
                        <a @click="cur_section='setting'" href="#settingCollectionBD"><span>百度收录查询</span><i class="tag-pro" v-if="!is_pro">Pro</i></a>
                        <a @click="cur_section='setting'" href="#settingBing"><span>Bing推送设置</span></a>
                        <a @click="cur_section='setting'" href="#settingLog"><span>日志记录设置</span></a>
                    </div>
                </li>
                <li class="tab-item" v-if="!isMobile()">
                    <a class="lv1" @click="switchMenu('about_pro')">
                        <i class="pro-btn"></i>
                    </a>
                </li>
            </ul>
        </div>

        <div class="wbs-content option-form" id="optionsframework">
			<?php
			settings_fields($setting_field);
			?>

            <div id="wbui99" class="wbui wbui-loading" index="999"><div class="wbui-main"><div class="wbui-section"><div class="wbui-child  wbui-anim-def"><div class="wbui-cont"><i></i><i class="wbui-load"></i><i></i><p></p></div></div></div></div></div>

            <?php include __DIR__.'/tpl/cnf.stats.php';?>

            <div class="sc-wp" v-if="cur_section == 'sp_404_url' || (isMobile() && cur_section=='overview')">
                <h3 class="sc-header">
                    <strong>死链提交清单</strong>
                </h3>
                <div class="sc-body log-box">
                    <div class="mt log-box">
                        <table class="wbs-table">
                            <thead>
                            <tr>
                                <th>URL地址</th>
                                <th>响应码状态</th>
                                <th>检测时间</th>
                                <th>操作</th>
                            </tr>
                            </thead>
                            <body>
                            <tr v-for="item in sp_404_url">
                                <td><div class="url"><a :href="item.url" target="_blank">{{item.url}}</a></div></td>
                                <td>{{item.code}}</td>
                                <td class="m-hide">{{item.visit_date}}</td>
                                <td class="m-hide">
                                    <a href="javascript:void(0);" @click="check_404_url(item)">刷新状态</a>
                                    <a href="javascript:void(0);" @click="del_404_url(item)">忽略</a>
                                </td>
                            </tr>
                            </body>
                        </table>
                        <div class="empty-tips-bar" v-show="!sp_404_url.length">
                            <span v-if="loading_data == -1">- 暂无数据 -</span>
                        </div>
                        <div class="btns-bar" v-show="sp_404_url.length >0 && log_loadmore.sp_404_url">
                            <a class="more-btn" @click="load_404_url(10)">查看更多</a>
                        </div>
                        <div class="mt" v-if="sp_404_url.length">
                            <input id="wb_bdsl_404-url" style="opacity: 0;" value="<?php echo home_url('/404-list.txt');?>" data-max="180" type="text" placeholder="" class="wbs-input">
                            <span class="ib vam"><b>清单文件</b>：<?php echo home_url('/404-list.txt');?></span>  <a id="J_copySubSML" onclick="var obj = jQuery('#wb_bdsl_404-url');obj.focus();obj.select();document.execCommand('Copy');wbui.toast('已复制');" class="button wbs-btn-copy ib" target="_blank"> 复制 </a>
                        </div>

                        <dl class="description mt">
                            <dt>温馨提示：</dt>
                            <dd>网站存在大量死链，将影响网站的站点评级，应及时处理网站死链。</dd>
                            <dd>如死链有可替代页面内容，建议采用301永久跳转方式对死链进行处理，<a class="link" target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/how-to-fix-404-error-in-wordpress.html">参考教程</a>。</dd>
                            <dd>如死链无可替代页面内容，则应复制死链清单链接，然后登录<a class="link" target="_blank" href="https://ziyuan.baidu.com/">百度搜索资源平台</a>进行死链提交。</dd>
							<dd><a class="link" target="_blank" href="http://zhanzhang.so.com/">360站长平台</a>、<a class="link" target="_blank" href="https://zhanzhang.toutiao.com/">头条搜索站长平台</a>和<a class="link" target="_blank" href="https://zhanzhang.sm.cn/">神马站长平台</a>也提供死链提交支持。</dd>
                            <dd>此死链检测仅检测网站内部链接。</dd>
                        </dl>
                        <?php if(!$spider_install || !$spider_active){?>
                        <div class="getpro-mask">
                            <div class="mask-inner">
                        <?php include BSL_PATH.'/tpl/spider_test.php';?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
            </div>


            <?php include __DIR__.'/tpl/cnf.log.php';?>

            <?php include __DIR__.'/tpl/cnf.option.php';?>


            <?php include __DIR__.'/tpl/cnf.about.php';?>


            <bing-push :section="cur_section" :opt="cnf" :is_pro="is_pro" :is_mobile="isMobile()" @switch-menu="switchMenu($event)"></bing-push>

            <more-wb-info v-bind:utm-source="pd_code"></more-wb-info>

            <div class="wb-copyright-bar">
                <div class="wbcb-inner">
                    <a class="wb-logo" href="https://www.wbolt.com" data-wba-campaign="footer" title="WBOLT" target="_blank"><svg class="wb-icon sico-wb-logo"><use xlink:href="#sico-wb-logo"></use></svg></a>
                    <div class="wb-desc">
                        Made By <a href="https://www.wbolt.com" data-wba-campaign="footer" target="_blank">闪电博</a>
                        <span class="wb-version">版本：<?php echo $pd_version;?></span>
                    </div>
                    <div class="ft-links">
                        <a href="https://www.wbolt.com/plugins" data-wba-campaign="footer" target="_blank">免费插件</a>
                        <a href="https://www.wbolt.com/knowledgebase" data-wba-campaign="footer" target="_blank">插件支持</a>
                        <a href="<?php echo $pd_doc_url; ?>" data-wba-campaign="footer" target="_blank">说明文档</a>
                        <a href="https://www.wbolt.com/terms-conditions" data-wba-campaign="footer" target="_blank">服务协议</a>
                        <a href="https://www.wbolt.com/privacy-policy" data-wba-campaign="footer" target="_blank">隐私条例</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wbs-footer" id="optionsframework-submit">
        <div class="wbsf-inner">
            <button class="wbs-btn-primary" type="submit" name="update">保存设置</button>
        </div>
    </div>
</form>

</div>

<?php include __DIR__.'/tpl/cnf.bing.php';?>


<div style=" display:none;">
    <svg aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden;" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
        <defs>
            <symbol id="sico-update" viewBox="0 0 12 15">
                <path fill-rule="evenodd" d="M10 9a4 4 0 11-4-4v3l5-4-5-4v3a6 6 0 106 6h-2z"/>
            </symbol>
            <symbol id="sico-upload" viewBox="0 0 16 13">
                <path d="M9 8v3H7V8H4l4-4 4 4H9zm4-2.9V5a5 5 0 0 0-5-5 4.9 4.9 0 0 0-4.9 4.3A4.4 4.4 0 0 0 0 8.5C0 11 2 13 4.5 13H12a4 4 0 0 0 1-7.9z" fill="#666" fill-rule="evenodd"/>
            </symbol>
            <symbol id="sico-wb-logo" viewBox="0 0 18 18">
                <title>sico-wb-logo</title>
                <path d="M7.264 10.8l-2.764-0.964c-0.101-0.036-0.172-0.131-0.172-0.243 0-0.053 0.016-0.103 0.044-0.144l-0.001 0.001 6.686-8.55c0.129-0.129 0-0.321-0.129-0.386-0.631-0.163-1.355-0.256-2.102-0.256-2.451 0-4.666 1.009-6.254 2.633l-0.002 0.002c-0.791 0.774-1.439 1.691-1.905 2.708l-0.023 0.057c-0.407 0.95-0.644 2.056-0.644 3.217 0 0.044 0 0.089 0.001 0.133l-0-0.007c0 1.221 0.257 2.314 0.643 3.407 0.872 1.906 2.324 3.42 4.128 4.348l0.051 0.024c0.129 0.064 0.257 0 0.321-0.129l2.25-5.593c0.064-0.129 0-0.257-0.129-0.321z"></path>
                <path d="M16.714 5.914c-0.841-1.851-2.249-3.322-4.001-4.22l-0.049-0.023c-0.040-0.027-0.090-0.043-0.143-0.043-0.112 0-0.206 0.071-0.242 0.17l-0.001 0.002-2.507 5.914c0 0.129 0 0.257 0.129 0.321l2.571 1.286c0.129 0.064 0.129 0.257 0 0.386l-5.979 7.264c-0.129 0.129 0 0.321 0.129 0.386 0.618 0.15 1.327 0.236 2.056 0.236 2.418 0 4.615-0.947 6.24-2.49l-0.004 0.004c0.771-0.771 1.414-1.671 1.929-2.7 0.45-1.029 0.643-2.121 0.643-3.279s-0.193-2.314-0.643-3.279z"></path>
            </symbol>
            <symbol id="sico-more" viewBox="0 0 16 16">
                <path d="M6 0H1C.4 0 0 .4 0 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M15 0h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1M6 9H1c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1M15 9h-5c-.6 0-1 .4-1 1v5c0 .6.4 1 1 1h5c.6 0 1-.4 1-1v-5c0-.6-.4-1-1-1"/>
            </symbol>
            <symbol id="sico-plugins" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M16 3h-2V0h-2v3H8V0H6v3H4v2h1v2a5 5 0 0 0 4 4.9V14H2v-4H0v5c0 .6.4 1 1 1h9c.6 0 1-.4 1-1v-3.1A5 5 0 0 0 15 7V5h1V3z"/>
            </symbol>
            <symbol id="sico-doc" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M15 0H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h14c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1zm-1 2v9h-3c-.6 0-1 .4-1 1v1H6v-1c0-.6-.4-1-1-1H2V2h12z"/><path d="M4 4h8v2H4zM4 7h8v2H4z"/>
            </symbol>
            <symbol id="wbsico-time" viewBox="0 0 18 18">
                <path d="M9 15.75c-3.71 0-6.75-3.04-6.75-6.75S5.29 2.25 9 2.25 15.75 5.29 15.75 9 12.71 15.75 9 15.75zM9 0C4.05 0 0 4.05 0 9s4.05 9 9 9 9-4.05 9-9-4.05-9-9-9z"/>
                <path d="M10.24 4.5h-1.8V9h4.5V7.2h-2.7z"/>
            </symbol>
            <symbol id="wbsico-views" viewBox="0 0 26 18">
                <path d="M13.1 0C7.15.02 2.08 3.7.02 8.9L0 9a14.1 14.1 0 0 0 13.09 9c5.93-.02 11-3.7 13.06-8.9l.03-.1A14.1 14.1 0 0 0 13.1 0zm0 15a6 6 0 0 1-5.97-6v-.03c0-3.3 2.67-5.97 5.96-5.98a6 6 0 0 1 5.96 6v.04c0 3.3-2.67 5.97-5.96 5.98zm0-9.6a3.6 3.6 0 1 0 0 7.2 3.6 3.6 0 0 0 0-7.2h-.01z"/>
            </symbol>
            <symbol id="wbsico-comment" viewBox="0 0 18 18">
                <path d="M9 0C4.05 0 0 3.49 0 7.88s4.05 7.87 9 7.87c.45 0 .9 0 1.24-.11L15.75 18v-4.95A7.32 7.32 0 0 0 18 7.88C18 3.48 13.95 0 9 0z"/>
            </symbol>
            <symbol id="sico-data" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M14 7h-2.5L10 9.2l-4-6L3.5 7H2V2h12v5zm0 7H2V9h2.5L6 6.8l4 6L12.5 9H14v5zm1-14H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h14c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1z"/>
            </symbol>
            <symbol id="sico-log" viewBox="0 0 14 16">
                <path fill-rule="evenodd" d="M13 0H1C.4 0 0 .4 0 1v14c0 .6.4 1 1 1h12c.6 0 1-.4 1-1V1c0-.6-.4-1-1-1zM3 11h4v2H3v-2zm0-4h8v2H3V7zm0-4h8v2H3V3z"/>
            </symbol>
            <symbol id="sico-setting" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M14 6h-4V4h4v2zm0 6h-2.3c-.3.6-1 1-1.7 1a2 2 0 01-2-2c0-1.1.9-2 2-2a2 2 0 011.7 1H14v2zM6 7a2 2 0 01-1.7-1H2V4h2.3c.3-.6 1-1 1.7-1a2 2 0 012 2 2 2 0 01-2 2zm0 5H2v-2h4v2zm8-12H2a2 2 0 00-2 2v12c0 1.1.9 2 2 2h12a2 2 0 002-2V2a2 2 0 00-2-2z"/>
            </symbol>
            <symbol id="sico-bing" viewBox="0 0 1024 1024">
                <path d="M99.6 0v863.3l232.5 158L924.4 668V418.8L408 260.8 519 488.2l147.4 69.4L121 850.5l217.4-206.9-7.6-582z"/>
            </symbol>
            <symbol id="sico-pro" viewBox="0 0 32 16">
                <g fill="none" fill-rule="evenodd">
                    <rect width="32" height="16" fill="#06C" rx="3"/>
                    <path fill="#FFF" fill-rule="nonzero" d="M8.2 12V8.8h1.1c1 0 1.8-.2 2.4-.8.7-.6 1-1.3 1-2.2 0-.8-.3-1.5-.8-2-.6-.5-1.3-.7-2.3-.7H7v9h1.2zm1-4.3h-1V4h1.2c1.3 0 2 .6 2 1.8 0 .6-.1 1-.5 1.4-.4.3-1 .5-1.6.5zm6.1 4.4V8.8c0-.7.2-1.2.5-1.6.3-.5.6-.7 1-.7l.9.2V5.6l-.6-.1c-.8 0-1.4.5-1.7 1.4V5.6h-1.2v6.5h1.1zm6 .1c1 0 1.8-.3 2.4-1 .6-.5 1-1.4 1-2.4s-.4-1.9-1-2.5a3 3 0 00-2.2-.9c-1 0-1.8.4-2.4 1-.6.6-1 1.4-1 2.5 0 1 .4 1.8 1 2.4a3 3 0 002.3 1zm.1-1a2 2 0 01-1.5-.6c-.4-.4-.6-1-.6-1.7 0-.8.2-1.4.6-1.8.4-.5.9-.7 1.5-.7.7 0 1.2.2 1.5.6.4.4.5 1 .5 1.8s-.1 1.4-.5 1.8c-.3.5-.8.7-1.5.7z"/>
                </g>
            </symbol>
            <symbol id="wbsico-notice" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 16A8 8 0 108 0a8 8 0 000 16zM7.2 4h1.6v4.8H7.2V4zm1.6 6.4H7.2V12h1.6v-1.6z" clip-rule="evenodd"/>
            </symbol>
        </defs>
    </svg>
</div>

<div id="not_found_spider" data-status="<?php echo (!$spider_install || !$spider_active)?1:0;?>" style="display: none">
    <?php include BSL_PATH.'/tpl/spider_test.php';?>
</div>