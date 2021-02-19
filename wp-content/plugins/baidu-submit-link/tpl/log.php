<?php
if(!defined('BSL_PATH'))return;
require_once __DIR__.'/common.php';
?>
<div class="v-wp wbs-wrap" id="optionsframework-wrap" data-wba-source="<?php echo $pd_code; ?>" v-cloak>
    <?php require_once __DIR__.'/header.php';?>
    <div class="wbs-main with-tab">
        <ul class="wbs-tab-nav">
            <li class="wb-tab-item"><router-link to="/baidu">百度推送日志</router-link></li>
            <li class="wb-tab-item"><router-link to="/bing">Bing推送日志</router-link></li>
            <li class="wb-tab-item"><router-link to="/qh">360推送日志</router-link></li>
            <li class="wb-tab-item"><router-link to="/run">插件执行日志</router-link></li>
        </ul>
        <div class="wbs-content option-form">
            <div id="wbui99" class="wbui wbui-loading" index="999"><div class="wbui-main"><div class="wbui-section"><div class="wbui-child  wbui-anim-def"><div class="wbui-cont"><i></i><i class="wbui-load"></i><i></i><p></p></div></div></div></div></div>

            <router-view></router-view>

            <?php require_once __DIR__.'/footer.php';?>

        </div>

    </div>
</div>

<template id="wb-bsl-log-baidu">
    <div class="sc-wp">
        <div v-if="type==1">
        <div class="cf">
            <div class="tab-nav style-b">
                <a class="tn-item current">普通收录推送</a>
                <a class="tn-item" @click="log_type(2)"><span>快速收录推送</span> <i class="tag-pro">Pro</i></a>
                <button type="button" class="button button-link fr" @click="clean_log(1)">清除日志</button>
            </div>
        </div>

        <div class="log-box mt">
            <table class="wbs-table" v-if="log_push">
                <thead>
                <tr>
                    <th>日期</th>
                    <th>链接</th>
                    <th>推送状态</th>
                </tr>
                </thead>
                <body>
                <tr v-for="item in log_push">
                    <td data-label="日期: ">{{item.date}}</td>
                    <td data-label="链接: "><div class="url">{{item.url}}</div></td>
                    <td data-label="状态: " v-html='item.s_push==1 ? "<span class=\"suc\">成功</span>": "失败"'></td>
                </tr>
                </body>
            </table>
            <div class="empty-tips-bar" v-show="!log_push.length">

                <span v-if="loading_data == -1">- 最近7天无推送数据，建议保持每日更新内容 -</span>
            </div>
            <div class="btns-bar" v-show="log_push.length>0">
                <a v-show="log_loadmore.push" class="more-btn" @click="loadLogRecord('tackpush',10)">查看更多</a>
            </div>

            <div class="description mt">温馨提示：日志仅记录近7天数据。<b>若推送状态为失败，请先自检问题（百度搜索资源平台认证域名与网站实际域名不一致情况较多，区分www和无www域名）</b>，若无法解决<a href="https://www.wbolt.com/member?act=enquire" data-wba-campaign="enquire" target="_blank">提交工单</a>反馈。</div>
        </div>
        </div>

        <div class="sc-block" v-if="type==2">
            <div class="cf">


                <div class="tab-nav style-b">
                    <a class="tn-item" @click="log_type(1)">普通收录推送</a>
                    <a class="tn-item current"><span>快速收录推送</span> <i class="tag-pro">Pro</i></a>
                    <button type="button" class="button button-link fr" @click="clean_log(2)">清除日志</button>
                </div>
            </div>

            <div class="log-box mt">
                <table class="wbs-table">
                    <thead>
                    <tr>
                        <th>日期</th>
                        <th>链接</th>
                        <th>推送状态</th>
                    </tr>
                    </thead>
                    <body>
                    <tr v-for="item in log_daypush">
                        <td data-label="日期: ">{{item.date}}</td>
                        <td data-label="链接: "><div class="url">{{item.url}}</div></td>
                        <td data-label="推送状态: " v-html='item.s_push==1 ? "<span class=\"suc\">成功</span>": "失败"'></td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!log_daypush.length">
                    <span v-if="loading_data == -1">- 最近7天无推送数据，建议保持每日更新内容 -</span>
                </div>
                <div class="btns-bar" v-if="log_daypush.length >0">
                    <a v-show="log_loadmore.daypush" class="more-btn" @click="loadLogRecord('daypush',10)">查看更多</a>
                </div>

                <div class="getpro-mask" v-if="!is_pro">
                    <div class="mask-inner">
                        <a class="wbs-btn-primary" @click="aboutPro">获取PRO版本</a>
                        <p class="tips">* 注意：当前为演示数据，仅供参考</p>
                    </div>
                </div>
            </div>
            <div class="description mt" v-if="is_pro">温馨提示：日志仅记录近7天数据。若推送状态为失败，多半是由于快速收录推送无配额或者配额已用完导致，建议使用插件执行日志自查，若无法解决<a href="https://www.wbolt.com/member?act=enquire" data-wba-campaign="enquire" target="_blank">提交工单</a>反馈。</div>

        </div>
    </div>
</template>

<template id="wb-bsl-log-bing">

    <div class="sc-wp">
        <div class="sc-block" v-if="type==1">
            <div class="cf">


                <div class="tab-nav style-b">
                    <a class="tn-item current">Bing手动推送</a>
                    <a class="tn-item" @click="log_type(2)"><span>Bing自动推送</span> <i class="tag-pro">Pro</i></a>
                    <button type="button" class="button button-link fr" @click="clean_log(11)">清除日志</button>
                </div>
            </div>

            <div class="log-box mt">
                <table class="wbs-table">
                    <thead>
                    <tr>
                        <th>日期</th>
                        <th>链接</th>
                        <th>推送状态</th>
                    </tr>
                    </thead>
                    <body>
                    <tr v-for="item in push_log_manual">
                        <td data-label="日期: ">{{item.date}}</td>
                        <td data-label="链接: "><div class="url">{{item.url}}</div></td>
                        <td data-label="状态: " v-html='item.s_push==1 ? "<span class=\"suc\">成功</span>": "失败"'></td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!push_log_manual.length">
                    <span v-if="loading_data == -1">- 最近7天无推送数据，建议保持每日更新内容 -</span>
                </div>
                <div class="btns-bar" v-show="push_log_manual.length>0">
                    <a v-show="load_more_manual" class="more-btn" @click="loadLogRecord('bing_manual',10)">查看更多</a>
                </div>

                <div class="mt"><button class="button button-cancel" type="button" @click="submit_urls"> 手动提交链接 </button></div>



                <div v-if="opt.bing_manual =='0'" class="getpro-mask">
                    <div class="mask-inner">
                        <a class="wbs-btn-primary j-get-pro" @click="switchMenu('cnf')">启用Bing手动推送</a>
                        <p class="tips">*注意：当前功能依赖Bing推送设置。当前该功能处于关闭状态，需启用后才可使用该功能。</p>
                    </div>
                </div>
            </div>
            <dl class="description mt">
                <dt>温馨提示：</dt>
                <dd>推送失败，请检测Bing推送API密钥是否正确及当前站点域名是否在Bing站长平台验证绑定。</dd>
                <dd>可以通过上方<b>手动提交链接</b>按钮批量手动推送URL数据至Bing。或者在Bing站长平台也可以批量手动提交URL，<a class="link" target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/how-to-submit-bing-urls-manually.html">查看教程</a>。</dd>
                <dd>如URL内容发生变化，可通过手动推送将最新的内容推送给Bing</dd>
                <dd>Bing推送URL配额每天为10000个，实质为推送次数，包含自动和手动推送的次数。</dd>
                <dd>Bing站长平台每天在格林尼治标准时间午夜重置配额，这可能与网站本地的时间不一致。</dd>
            </dl>
        </div>
        <div class="sc-block" v-if="type==2">
            <div class="cf">


                <div class="tab-nav style-b">
                    <a class="tn-item" @click="log_type(1)">Bing手动推送</a>
                    <a class="tn-item current"><span>Bing自动推送</span> <i class="tag-pro">Pro</i></a>
                    <button type="button" class="button button-link fr" @click="clean_log(10)">清除日志</button>
                </div>
            </div>

            <div class="log-box mt">
                <table class="wbs-table">
                    <thead>
                    <tr>
                        <th>日期</th>
                        <th>链接</th>
                        <th>推送状态</th>
                    </tr>
                    </thead>
                    <body>
                    <tr v-for="item in push_log">
                        <td data-label="日期: ">{{item.date}}</td>
                        <td data-label="链接: "><div class="url">{{item.url}}</div></td>
                        <td data-label="状态: " v-html='item.s_push==1 ? "<span class=\"suc\">成功</span>": "失败"'></td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!push_log.length">
                    <span v-if="loading_data == -1">- 最近7天无推送数据，建议保持每日更新内容 -</span>
                </div>

                <div class="btns-bar" v-show="push_log.length>0">
                    <a v-show="load_more" class="more-btn" @click="loadLogRecord('bing_auto',10)">查看更多</a>
                </div>
                <div v-if="is_pro && opt.bing_auto == '0'" class="mt">   &nbsp;</div>

                <div v-if="is_pro && opt.bing_auto == '0'" class="getpro-mask">
                    <div class="mask-inner">
                        <a class="wbs-btn-primary j-get-pro" @click="switchMenu('cnf')">启用收录查询</a>
                        <p class="tips">*注意：当前功能依赖百度收录查询。当前该功能处于关闭状态，需启用后才可使用文章收录清单功能。</p>
                    </div>
                </div>

            </div>
            <dl class="description mt">
                <dt>温馨提示：</dt>
                <dd>推送失败，请检测Bing推送API密钥是否正确及当前站点域名是否在Bing站长平台验证绑定。</dd>
                <dd>Bing自动推送数据类型包括新发布的、更新的及删除的URL数据。</dd>
                <dd>Bing推送URL配额每天为10000个，实质为推送次数，包含自动和手动推送的次数。</dd>
                <dd>Bing站长平台每天在格林尼治标准时间午夜重置配额，这可能与网站本地的时间不一致。</dd>
            </dl>
        </div>


    </div>


</template>

<template id="wb-bsl-log-qh">
    <div class="sc-wp">
        <div class="sc-block" v-if="type==1">
            <div class="cf">

                <div class="tab-nav style-b">
                    <button type="button" class="button button-link fr" @click="clean_log(20)">清除日志</button>
                </div>
            </div>

            <div class="log-box mt">
                <table class="wbs-table">
                    <thead>
                    <tr>
                        <th>日期</th>
                        <th>链接</th>
                        <th>推送状态</th>
                    </tr>
                    </thead>
                    <body>
                    <tr v-for="item in push_log">
                        <td data-label="日期: ">{{item.date}}</td>
                        <td data-label="链接: "><div class="url">{{item.url}}</div></td>
                        <td data-label="状态: " v-html='item.s_push==1 ? "<span class=\"suc\">成功</span>": "失败"'></td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!push_log.length">
                    <span v-if="loading_data == -1">- 最近7天无推送数据，建议保持每日更新内容 -</span>
                </div>
                <div class="btns-bar" v-show="push_log.length>0">
                    <a v-show="load_more" class="more-btn" @click="loadLogRecord('360_auto',10)">查看更多</a>
                </div>




                <div v-if="opt.qh_active=='0'" class="getpro-mask">
                    <div class="mask-inner">
                        <a class="wbs-btn-primary j-get-pro" @click="switchMenu('cnf')">启用360自动推送</a>
                        <p class="tips">*注意：当前功能依赖360自动推送设置。当前该功能处于关闭状态，需启用后才可使用该功能。</p>
                    </div>
                </div>
            </div>
            <dl class="description mt">
                <dt>温馨提示：</dt>
                <dd>360无推送日志，请确保插件设置是否正确贴入360站长平台域名对应的自动收录JS代码。</dd>

            </dl>
        </div>



    </div>
</template>

<template id="wb-bsl-log-run">
    <div class="sc-wp" id="settingLog">
        <div class="log-box">
            <table class="wbs-table">
                <thead>
                <tr>
                    <td width="20%">时间</td>
                    <td width="15%">类型</td>
                    <td width="*">详情</td>
                </tr>
                </thead>
            </table>
            <div class="log-wp" style="padding:0;" id="running_log">
                <table class="wbs-table">
                    <tbody>
                    <tr v-for="r in run_log">
                        <td data-label="时间: " width="20%">{{r.time}}</td>
                        <td data-label="类型: " width="15%">{{r.type}}</td>
                        <td data-label="详情: ">{{r.msg}}</td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="empty-tips-bar" v-show="!run_log.length">
                <span v-if="loading_data == -1">- 暂无数据 -</span>
            </div>

            <div class="mt">
                <button class="button button-cancel" type="button" @click="clear_log()"> 清除日志 </button>
                <button class="button button-primary" type="button" @click="reload_log()"> 刷新日志 </button>
            </div>

            <div class="getpro-mask" v-if="!is_pro">
                <div class="mask-inner">
                    <a class="wbs-btn-primary" @click="aboutPro()">获取PRO版本</a>
                    <p class="tips">* 注意：当前为演示数据，仅供参考</p>
                </div>
            </div>

        </div>
    </div>
</template>
<?php
require_once __DIR__.'/svg.php';
?>