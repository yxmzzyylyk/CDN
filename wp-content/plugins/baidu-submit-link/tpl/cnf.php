<?php
if(!defined('BSL_PATH'))return;
require_once __DIR__.'/common.php';
?>
<div class="wbs-wrap v-wp" id="optionsframework-wrap" data-wba-source="<?php echo $pd_code; ?>" v-cloak>
    <?php require_once __DIR__.'/header.php';?>
    <div class="wbs-main with-tab">
        <ul class="wbs-tab-nav">
            <li class="wb-tab-item" :class="{current:tab=='base'}" @click="tab='base'"><a href="javascript:;">常规设置</a></li>
            <li class="wb-tab-item" :class="{current:tab=='api'}" @click="tab='api'"><a href="javascript:;">推送API</a></li>
        </ul>
        <div class="wbs-content option-form">
            <div class="sc-wp" v-show="tab=='base'">
                <div class="sc-body">
                    <table class="wbs-form-table">
                        <tbody>
                        <tr>
                            <th class="row w8em">推送链接类型
                                <div class="wbui-tooltip" data-msg="不建议推送媒体、页面链接类型至百度"><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td>
                                <div class="selector-bar">
                                    <label v-for="(r,k) in init_data.post_types"><input type="checkbox" v-model="cnf.post_type" :value="k"/>{{r}}</label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>日志保留时间
                                <div class="wbui-tooltip" data-msg="网站文章数据较多，可以考虑较短日志保留时间。"><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td>
                                <div class="selector-bar">
                                    <label v-for="(r,k) in init_data.log_day"> <input type="radio" v-model="cnf.log_day" :value="k"/> {{r}} </label>
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <th>死链检测设置
                            </th>
                            <td>
                                <input class="wb-switch" type="checkbox" true-value="1" false-value="0" v-model="cnf.check_404">

                                <div class="description mt">*死链检测依赖Spider Analyser-蜘蛛分析插件。
                                    <div class="wb-hl wb-hl-inline" v-if="!init_data.spider_install">
                                        <svg class="wb-icon wbsico-notice"><use xlink:href="#wbsico-notice"></use></svg>
                                        <span>未检测到Spider Analyser安装，去</span> <a class="link" :href="init_data.spider_setup_url">安装</a>
                                    </div>
                                    <div class="wb-hl wb-hl-inline" v-if="!init_data.spider_active">
                                        <svg class="wb-icon wbsico-notice"><use xlink:href="#wbsico-notice"></use></svg>
                                        <span>未检测到Spider Analyser启用，去</span> <a class="link" :href="init_data.spider_setup_url">启用</a>
                                    </div>
                                </div>
                                <div class="description mt">*对死链及时处理有利于网站权重，建议开启该功能。</div>
                            </td>
                        </tr>
						
                        <tr>
                            <th>收录查询设置
                            </th>
                            <td>
                                <input class="wb-switch" type="checkbox" @click="pro_click($event)" true-value="1" false-value="0" v-model="cnf.in_bd_active">
                                <span class="description">开启百度收录状态查询</span>
                                <i class="tag-pro" @click="aboutPro()">Pro</i>
                                <dl class="description mt">
                                    <dt>温馨提示：</dt>
                                    <dd>开启该功能后，采用插件API方式查询文章百度收录状态。</dd>
                                    <dd>在已发布文章列表快速编辑选项增加百度收录状态、百度收录查询入口及未收录链接提交选项。</dd>
                                    <dd><b>文章百度收录状态仅供参考，实际收录情况以百度搜索为准</b>。</dd>
                                    <dd>如关闭此开关，文章收录清单不再新增数据。</dd>
                                </dl>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>


            <div class="sc-wp" v-show="tab=='api'">

                <h3 class="sc-header">
                    <strong>百度推送设置</strong>
                </h3>
                <div class="sc-body">
                    <table class="wbs-form-table">
                        <tbody>
                        <tr>
                            <th class="row w8em">接口调用地址
                                <div class="wbui-tooltip" data-msg="填写普通收录或者快速收录任意一个API提交推送接口调用地址即可。"><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td>
                                <input class="wbs-input" data-max="180" v-model="cnf.token"  type="text" placeholder="">
                            </td>
                        </tr>
                        <tr>
                            <th class="row">推送方式</th>
                            <td class="info">
                                <label class="when-m-block"><input class="wb-switch" type="checkbox" true-value="1" false-value="0" v-model="cnf.pc_active" > <span>普通收录主动推送</span></label>
                                <!--<label class="ml when-m-block"><input class="wb-switch" type="checkbox" true-value="1" false-value="0" v-model="cnf.bdauto" value="1" id="seo_bdauto"> <span>普通收录自动推送</span></label>-->
                                <label class="ml when-m-block"><input class="wb-switch" type="checkbox" true-value="1" false-value="0" @click="pro_click($event)" v-model="cnf.daily_active" > <span>快速收录推送 <i class="tag-pro" @click="aboutPro()">Pro</i></span></label>

                                <div class="mt when-m-block">
                                    <label><input class="wb-switch" type="checkbox" checked name="sitemap_push" disabled value="1"> <span>Sitemap地图推送</span></label>
                                    <!--检测是否开启sitemap 若未有：-->
                                    <span class="description ib ml" v-if="!init_data.sitemap_exists">未检测到有效站点Sitemap，请依据下方说明安装插件生成站点sitemap</span>
                                    <!--若有：-->
                                    <span class="description ib ml" v-if="init_data.sitemap_exists"><a class="sitemap" :href="init_data.sitemap_url" rel="noreferrer" target="_blank">{{init_data.sitemap_url}}</a></span>
                                </div>
                                <dl class="description mt">
                                    <dt>温馨提示：</dt>								
                                    <dd v-if="cnf.bdauto">已启用百度链接自动推送，切莫重复添加推送工具代码。</dd>
                                    <dd v-if="!cnf.bdauto"> 自动推送开关开启后，主题会添加自动推送工具代码，提高百度搜索引擎对站点新增网页发现速度。</dd>
                                    <dd>快速收录推送需要先在百度搜索资源平台获得快速收录配额。</dd>
                                    <dd>Sitemap生成 - 下载并启动Sitemap生成插件，建议安装<a lass="link" target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/plugins/sst" title="SEO插件">Smart SEO Tool</a>或者Google XML Sitemaps。<a lass="link" target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/how-to-set-google-xml-sitemaps.html">查看教程</a></dd>
                                    <dd>Sitemap提交 - 访问并登陆<a class="link" target="_blank" href="https://ziyuan.baidu.com/">百度搜索资源平台</a>，找到链接提交-自动提交-sitemap，填入非索引型sitemap地址，最后提交。<a class="link" target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/submit-sitemap-url-to-baidu.html">查看教程</a></dd>
                                    <dd>sitemap检测仅支持主流sitemap插件，如无法检测，请手动复制非索引型sitemap地址到百度搜索资源平台提交。</dd>
                                </dl>								
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div>

                <h3 class="sc-header">
                    <strong>Bing推送设置</strong>
                </h3>
                <div class="sc-body setting-box">
                    <table class="wbs-form-table">
                        <tbody>
                        <tr>
                            <th class="row w8em">API密钥
                                <div class="wbui-tooltip" data-msg="访问Bing网站管理员工具生成API密钥."><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td>
                                <input  class="wbs-input" data-max="180" v-model="cnf.bing_key"  type="text" placeholder="">
                            </td>
                        </tr>
                        <tr>
                            <th class="row w8em">推送方式</th>
                            <td class="info">
                                <label><input class="wb-switch" type="checkbox" @click="pro_click($event)" true-value="1" false-value="0" v-model="cnf.bing_auto"><span>自动推送 <i class="tag-pro" @click="aboutPro()">Pro</i></span></label>
                                <label class="ml"><input class="wb-switch" type="checkbox" true-value="1" false-value="0" v-model="cnf.bing_manual"><span>手动推送</span></label>
                                <dl class="description mt">
                                    <dt>温馨提示：</dt>
                                    <dd>无论使用Bing自动推送还是手动推送，都务必先配置API密钥，否则无法正常推送。</dd>
                                    <dd>如启用了Bing自动推送，又无需使用手动推送，可以考虑把手动推送关闭。</dd>
                                    <dd>目前Bing自动推送包含了新建、更新和删除等类型推送，因此同一URL多次推送是正常现象。</dd>
									<dd>如何生成Bing自动推送API密钥，<a target="_blank" data-wba-campaign="Setting-Des-txt" href="https://www.wbolt.com/generate-bing-api-key.html" class="link">查看教程</a></dd>
                                </dl>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>


                <h3 class="sc-header">
                    <strong>360推送设置</strong>

                </h3>
                <div class="sc-body setting-box">
                    <table class="wbs-form-table">
                        <tbody>
                        <tr>
                            <th class="row w8em">推送JS代码
                                <div class="wbui-tooltip" data-msg="访问登录360站长平台-数据提交-自动收录，获取JS代码。"><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td>
                                <textarea class="wbs-input" data-max="200" v-model="cnf.qhjs" rows="5" cols="42"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th class="row">批量推送
								<div class="wbui-tooltip" data-msg="在360官方推送JS的基础上，批量推送访客访问页面上的所有站点域名链接。"><svg class="wb-icon sico-qa"><use xlink:href="#sico-qa"></use></svg></div>
                            </th>
                            <td class="info">
                                <label>
								  <input class="wb-switch" @click="pro_click($event)" type="checkbox" true-value="1" false-value="0" v-model="cnf.qh_batch"> <i class="tag-pro" @click="aboutPro()">Pro</i>
								</label>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

            </div>
            <?php require_once __DIR__.'/footer.php';?>
        </div>
        <div class="wbs-footer" id="optionsframework-submit">
            <div class="wbsf-inner">
                <button class="wbs-btn-primary" type="button" @click="save()" name="update">保存设置</button>
            </div>
        </div>
    </div>

</div>
<?php
require_once __DIR__.'/svg.php';
?>