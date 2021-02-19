<?php
if(!defined('BSL_PATH'))return;
?>
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