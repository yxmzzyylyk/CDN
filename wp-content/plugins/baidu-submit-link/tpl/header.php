<?php
if(!defined('BSL_PATH'))return;
?>
<div id="version_tips" v-if="new_ver">
    <div class="update-message notice inline notice-warning notice-alt">
        <p>当前<?php echo $pd_title;?>有新版本可用. <a href="<?php echo $pd_index_url; ?>" data-wba-campaign="notice-bar#J_updateRecordsSection" target="_blank">查看版本<span class="ver">{{new_ver}}</span> 详情</a>
            或 <a href="<?php echo admin_url('/plugins.php?plugin_status=upgrade');?>" class="update-link" aria-label="现在更新<?php echo $pd_title;?>">现在更新</a>.
        </p>
    </div>
</div>
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
