<?php
if(!defined('BSL_PATH'))return;
require_once __DIR__.'/common.php';
?>
<div class="wbs-wrap v-wp" id="optionsframework-wrap" data-wba-source="<?php echo $pd_code; ?>" v-cloak>
    <?php require_once __DIR__.'/header.php';?>
    <div class="wbs-main with-tab">


            <ul class="wbs-tab-nav">
                <li class="wb-tab-item"><router-link to="/base">整站收录统计</router-link></li>
                <li class="wb-tab-item"><router-link to="/stats">搜索推送统计</router-link></li>
                <li class="wb-tab-item"><router-link to="/idx-post">文章收录清单</router-link></li>
                <li class="wb-tab-item"><router-link to="/404-post">死链提交清单</router-link></li>
            </ul>
            <div class="wbs-content option-form">
                <div id="wbui99" class="wbui wbui-loading" index="999"><div class="wbui-main"><div class="wbui-section"><div class="wbui-child  wbui-anim-def"><div class="wbui-cont"><i></i><i class="wbui-load"></i><i></i><p></p></div></div></div></div></div>

                <router-view></router-view>

                <?php require_once __DIR__.'/footer.php';?>

            </div>

    </div>
</div>

<template id="wb-tpl-bsl-base">
    <div class="sc-wp overview-with-charts">
        <h3 class="sc-header ov-header">
            <div class="ov-ctrl"><span>最后更新：<?php echo get_option('wb_idx_data_updated','-');?></span> <a class="btn-with-svg" @click="update_index_data()" onclick="this.classList.add('active');"><svg class="wb-icon sico-update"><use xlink:href="#sico-update"></use></svg> <span>手动更新</span></a></div>
            <strong>收录概况</strong>
        </h3>
        <div class="sc-body">
            <div class="data-overview">
                <div class="ao-it" v-for="item in overview">
                    <dl>
                        <dt class="it-name">{{item.name}}</dt>
                        <dd class="it-value">{{item.value}}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="overview-charts">
            <div class="tab-nav">
                <a class="tn-item" :class="{current: day==7}" @click="collectionOverview(7)">近7天</a>
                <a class="tn-item" :class="{current: day==30}" @click="collectionOverview(30)">近30天</a>
            </div>
            <v-chart class="charts-box" :options="chart_cnf"/>
        </div>
        <dl class="description mt">
            <dt>温馨提示：</dt>
            <dd><b>整站收录数据为估算值</b>，本地查询数据每6小时更新一次，API查询数据每天更新一次。网站管理员如需了解更准确的索引量，请使用百度站长平台。</dd>
            <dd>百度收录数据可能与百度搜索引擎查询有一定的差异，这跟不同地域不同客户端不同时间等因素搜索有关。</dd>
            <dd v-if="!is_pro"><b>整站收录概况查询提示“请求返回【没有找到数据】”</b>，这是由于百度防爬虫机制导致。建议<a class="link" style="cursor: pointer;" @click="aboutPro">升级Pro版本</a>，使用百度收录查询API数据。</dd>
        </dl>
    </div>
</template>

<template id="wb-tpl-bsl-stats-baidu">
    <div class="sc-group">
        <div class="sc-block">
            <h3 class="sc-header">
                <strong>百度普通收录推送统计</strong>
            </h3>

            <div class="sc-body">
                <div class="tab-nav mt" v-if="0">
                    <a class="tn-item" :class="{current: day==7}" @click="pushOverview(7)">近7天</a>
                    <a class="tn-item" :class="{current: day==30}" @click="pushOverview(30)">近30天</a>
                </div>

                <div class="charts-wp">
                    <div class="chart">
                        <v-chart class="charts-box" :options="chart_cnf"/>
                    </div>
                </div>

                <dl class="description mt">
                    <dt>数据说明：</dt>
                    <dd>这里数据仅代表插件协作推送至百度搜索资源平台的数据，即完整推送数据；</dd>
                    <dd>百度搜索资源平台的统计数据为主动和sitemap推送方式去重数据，除sitemap推送外，主动推送不重复计算已经收录或者推送过的数据；</dd>
                    <dd>推送数据不代表收录数据，积极向百度推送数据目的是为了更好地获得收录数据。</dd>
                    <dd>对于已推送过的数据，不作重复推送，避免百度判断站点推送内容质量度低。</dd>
                </dl>
            </div>
        </div>

        <div class="sc-block">
            <h3 class="sc-header">
                <strong>百度快速收录推送统计</strong><i class="tag-pro" @click="aboutPro()">Pro</i>
            </h3>

            <div class="sc-body">
                <div class="tab-nav mt" v-if="0">
                    <a class="tn-item" :class="{current: day==7}" @click="dayPush(7)">近7天</a>
                    <a class="tn-item" :class="{current: day==30}" @click="dayPush(30)">近30天</a>
                </div>

                <div class="charts-wp mt">
                    <div class="chart"><v-chart class="charts-box" :options="chart_cnf_pro"/></div>

                    <div v-if="!is_pro" class="getpro-mask">
                        <div class="mask-inner">
                            <a class="wbs-btn-primary j-get-pro" @click="aboutPro">获取PRO版本</a>
                            <p class="tips">* 注意：当前为随机演示数据，仅供参考</p>
                        </div>
                    </div>
                </div>
                <dl class="description mt">
                    <dt>温馨提示：</dt>
                    <dd>这里仅统计快速收录推送数据，快速收录推送收录情况请访问<a class="link" target="_blank" href="https://ziyuan.baidu.com/">百度搜索资源平台</a>查看。</dd>
                    <dd>快速收录推送收录数据有时候会出现1周的数据延迟或无数据的情况，这是百度系统问题。</dd>
                </dl>
            </div>
        </div>
    </div>
</template>
<template id="wb-tpl-bsl-stats-bing">
    <div class="sc-block">
        <h3 class="sc-header">
            <strong>Bing推送统计</strong>
        </h3>

        <div class="sc-body">

            <div class="tab-nav" v-if="0">
                <a class="tn-item" :class="{current: day==7}" @click="overview_data(7)">近7天</a>
                <a class="tn-item" :class="{current: day==30}" @click="overview_data(30)">近30天</a>
            </div>
            <div class="charts-wp">
                <v-chart class="charts-box" :options="chart_cnf"/>
            </div>
        </div>

        <div class="sc-body">
            <div class="description mt align-center">
                <span class="ml" v-for="item in overview">
                    {{item.name}}: {{item.value}}
                </span>
            </div>
        </div>
    </div>
</template>
<template id="wb-tpl-bsl-stats-qh">
    <div class="sc-block">
        <h3 class="sc-header">
            <strong>360推送统计</strong>
        </h3>
        <div class="sc-bodys">

            <div class="tab-nav" v-if="0">
                <a class="tn-item" :class="{current: day==7}" @click="overview_data(7)">近7天</a>
                <a class="tn-item" :class="{current: day==30}" @click="overview_data(30)">近30天</a>
            </div>
            <div class="charts-wp">
                <v-chart class="charts-box" :options="chart_cnf"/>
            </div>
        </div>
    </div>
</template>


<template id="wb-tpl-bsl-stats">
    <div class="sc-wp">
        <div class="tab-nav style-c">
            <a class="tn-item" :class="{current: day==7}" @click="set_day(7)">近7天</a>
            <a class="tn-item" :class="{current: day==30}" @click="set_day(30)">近30天</a>
        </div>

        <bsl-stats-baidu :day="day"></bsl-stats-baidu>

        <div class="sc-group">
            <bsl-stats-bing :day="day"></bsl-stats-bing>
            <bsl-stats-qh :day="day"></bsl-stats-qh>
        </div>
    </div>
</template>

<?php /**
 * 文章收录清单
 */?>
<template id="wb-tpl-bsl-idx">
    <div class="sc-wp">
        <div class="log-box">
            <div class="tab-nav style-b">
                <a class="tn-item" :class="{current:type==1}" @click="switch_log_baidu(1)">所有文章</a>
                <a class="tn-item" :class="{current:type==2}" @click="switch_log_baidu(2)">已收录文章</a>
                <a class="tn-item" :class="{current:type==3}" @click="switch_log_baidu(3)">未收录文章</a>


                <span class="btn-update disabled" v-if="last_check_date != ''">
                            <span>当前站点已完成{{query_times}}次收录查询 ,状态最后更新时间: {{last_check_date}} </span>
                            <a v-if="check_all == 0 && is_pro && opt.in_bd_active" class="tn-item" @click="check_all_post()">全量检测</a>
                        </span>
            </div>

            <div class="mt log-box">
                <table class="wbs-table">
                    <thead>
                    <tr>
                        <th>标题/URL</th>
                        <th>发布日期</th>
                        <th>收录状态</th>
                        <th>最近检测时间</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <body>
                    <tr v-for="item in log_baidu">
                        <td><a data-label="标题/URL: " :href="item.post_url" target="_blank"><span>{{item.post_title}}</span></a></td>
                        <td><div class="data-label" data-label="发布日期: ">{{item.post_date}}</div></td>
                        <td><div data-label="状态: " v-html='item.in_baidu==1 ? "<span class=\"suc\">已收录</span>": (item.in_baidu == 2?"未收录":"检测中")'></div></td>
                        <td><div class="data-label" data-label="最近检测: ">{{item.in_baidu  ? item.last_date : ''}}</div></td>
                        <td class="wb-ctrl-items">
                            <a class="ib" href="javascript:void(0);" @click="check_baidu(item)">检测收录</a>
                            <a class="ib" href="javascript:void(0);" @click="spider_history(item)">蜘蛛历史</a>
                        </td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!log_baidu.length">
                    <span v-if="loading_data == -1">- 暂无数据 -</span>
                </div>
                <div class="btns-bar" v-show="log_baidu.length >0 && loadmore">
                    <a class="more-btn" @click="loadBaiduRecord(10)">查看更多</a>
                </div>

                <dl class="description mt">
                    <dt>温馨提示：</dt>
                    <dd><b>文章百度收录状态仅供参考，实际收录情况以百度搜索为准；</b></dd>
                    <dd>百度升级了搜索验证码机制，收录查询结果可能会出现失败导致结果不准；</dd>
                    <dd>插件根据实际情况不定时检测文章百度收录情况，依据文章新旧赋予查询权重；</dd>
                    <dd>不建议使用过长的URL链接，不利于SEO优化且超出规定长度，无法查询该URL的收录状态；</dd>
                    <dd>网站仅支持一次全量文章收录状态检测;</dd>
                    <dd>每个周日为百度收录查询API服务器维护日，不执行收录查询工作。</dd>
                </dl>

            </div>
            <div v-if="!is_pro" class="getpro-mask">
                <div class="mask-inner">
                    <a class="wbs-btn-primary j-get-pro" @click="aboutPro()">获取PRO版本</a>
                    <p class="tips">* 注意：当前为随机演示数据，仅供参考</p>
                </div>
            </div>
            <div v-if="is_pro && opt.in_bd_active != '1'" class="getpro-mask">
                <div class="mask-inner">
                    <a class="wbs-btn-primary j-get-pro" @click="switchMenu('cnf')">启用收录查询</a>
                    <p class="tips">*注意：当前功能依赖百度收录查询。当前该功能处于关闭状态，需启用后才可使用文章收录清单功能。</p>
                </div>
            </div>
        </div>
    </div>
</template>

<?php /**
* 死链提交清单
 */?>
<template id="wb-tpl-bsl-404">
    <div class="sc-wp">
        <div class="log-box">
            <div class="log-box">
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
                        <td><div class="url"><a data-label="URL: " :href="item.url" target="_blank"><span>{{item.url}}</span></a></div></td>
                        <td><div class="data-label" data-label="响应码: ">{{item.code}}</div></td>
                        <td><div class="data-label" data-label="检测时间: ">{{item.visit_date}}</div></td>
                        <td class="wb-ctrl-items">
                            <a href="javascript:void(0);" @click="check_404_url(item)">刷新状态</a>
                            <a href="javascript:void(0);" @click="del_404_url(item)">忽略</a>
                        </td>
                    </tr>
                    </body>
                </table>
                <div class="empty-tips-bar" v-show="!sp_404_url.length">
                    <span v-if="loading_data == -1">- 暂无数据 -</span>
                </div>
                <div class="btns-bar" v-show="sp_404_url.length >0 && loadmore">
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
                <div class="getpro-mask" v-if="!bsl_data.spider_install || !bsl_data.spider_active">
                    <div class="mask-inner">
                    <div class="tips" v-if="!bsl_data.spider_install">
                        <p>* 当前功能依赖Spider Analyser-蜘蛛分析插件。</p>
                        <div class="wb-hl mt">
                            <svg class="wb-icon wbsico-notice"><use xlink:href="#wbsico-notice"></use></svg>
                            <span>未检测到安装，去</span>
                            <a class="link" :href="bsl_data.spider_setup_url">安装</a>
                        </div>
                    </div>
                    <div class="tips" v-if="!bsl_data.spider_active">
                        <p>* 当前功能依赖Spider Analyser-蜘蛛分析插件。</p>
                        <div class="wb-hl mt">
                            <svg class="wb-icon wbsico-notice"><use xlink:href="#wbsico-notice"></use></svg>
                            <span>检测到未启用，去</span>
                            <a class="link" :href="bsl_data.spider_setup_url">启用</a>
                        </div>
                    </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</template>

<?php
require_once __DIR__.'/svg.php';
?>