<?php
defined( 'ABSPATH' ) || exit;

add_action('wp_footer', 'wpcom_footer', 1);
if(!function_exists('wpcom_footer')){
    function wpcom_footer(){
        global $options;
        $style = isset($options['action_style']) && $options['action_style'] ? $options['action_style'] : '0';
        $cstyle = isset($options['action_cstyle']) && $options['action_cstyle'] ? $options['action_cstyle'] : '0';
        $pos = isset($options['action_pos']) && $options['action_pos'] ? $options['action_pos'] : '0';
        ?>
        <div class="action action-style-<?php echo $style;?> action-color-<?php echo $cstyle;?> action-pos-<?php echo $pos;?>"<?php echo isset($options['action_bottom'])?' style="bottom:'.$options['action_bottom'].';"':''?>>
            <?php
            if(isset($options['action_icon']) && $options['action_icon']){
                foreach ($options['action_icon'] as $i => $icon){
                    if($icon){
                        $title = $options['action_title'][$i];
                        $type = $options['action_type'][$i];
                        $target = $options['action_target'][$i];
                        if($type==='0'){ ?>
                            <a class="action-item" <?php echo WPCOM::url($target);?>>
                                <?php WPCOM::icon($icon, true, 'action-item-icon');?>
                                <?php if($style) echo '<span>'.$title.'</span>';?>
                            </a>
                        <?php }else{
                            ?>
                            <div class="action-item">
                                <?php WPCOM::icon($icon, true, 'action-item-icon');?>
                                <?php if($style) echo '<span>'.$title.'</span>';?>
                                <div class="action-item-inner action-item-type-<?php echo $type;?>">
                                    <?php if($type==='1') {
                                        echo '<img class="action-item-img" src="'.esc_url($target).'" alt="'.esc_attr($title).'">';
                                    }else{
                                        echo wpautop($target);
                                    }?>
                                </div>
                            </div>
                        <?php } ?>
                    <?php }
                }
            } ?>
            <?php if(isset($options['share'])&&$options['share']=='1'){ ?>
                <div class="action-item j-share">
                    <i class="action-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M0 0h24v24H0z" fill="none"/><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/></svg>
                    </i>
                    <?php if($style) echo '<span>'.__('SHARE', 'wpcom').'</span>';?>
                </div>
            <?php }
            if ((isset($options['gotop']) && $options['gotop'] == '1') || !isset($options['gotop'])) { ?>
                <div class="action-item gotop j-top">
                    <i class="action-item-icon">
                        <svg viewBox="0 0 24 24"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M4 12l1.41 1.41L11 7.83V20h2V7.83l5.58 5.59L20 12l-8-8-8 8z"/></svg>
                    </i>
                    <?php if($style) echo '<span>'.__('TOP', 'wpcom').'</span>';?>
                </div>
            <?php } ?>
        </div>
        <?php if(isset($options['footer_bar_icon']) && !empty($options['footer_bar_icon'])){ ?>
            <div class="footer-bar">
                <?php $i = 0; foreach($options['footer_bar_icon'] as $fb){ if($fb){
                    $type = isset($options['footer_bar_type'][$i]) && $options['footer_bar_type'][$i]=='1' ? $options['footer_bar_type'][$i] : '0';
                    $bg = isset($options['footer_bar_bg'][$i]) && $options['footer_bar_bg'][$i] ? ' style="background-color: '.WPCOM::color($options['footer_bar_bg'][$i]).';"' : '';
                    $color = isset($options['footer_bar_color'][$i]) && $options['footer_bar_color'][$i] ? ' style="color: '.WPCOM::color($options['footer_bar_color'][$i]).';"' : '';?>
                    <div class="fb-item"<?php echo $bg;?>>
                        <a <?php echo WPCOM::url($options['footer_bar_url'][$i]);?><?php if($type=='1'){ echo ' class="j-footer-bar-icon"';} echo $color;?>>
                            <?php WPCOM::icon($fb, true, 'fb-item-icon');?>
                            <span><?php echo $options['footer_bar_title'][$i];?></span>
                        </a>
                    </div>
                    <?php } $i++;} ?>
            </div>
        <?php }
    }
}

add_action('wp_footer', 'wpcom_footer_share_js', 999);
if(!function_exists('wpcom_footer_share_js')){
    function wpcom_footer_share_js(){
        global $options; ?>
        <?php if(isset($options['share'])&&$options['share']=='1' && get_locale()=='zh_CN'){ ?>
            <script>setup_share(1);</script>
        <?php } else if(isset($options['share']) && $options['share']=='1') { ?>
            <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-542188574c8ebd62"></script>
            <script>setup_share();</script>
        <?php }
    }
}

if(!function_exists('wpcom_footer_class')){
    function wpcom_footer_class($class=''){
        global $options;
        $_class = 'footer';
        if(isset($options['footer_bar_icon']) && !empty($options['footer_bar_icon']) && $options['footer_bar_icon'][0]) $_class .= ' width-footer-bar';
        if($class) $_class .= ' ' . $class;
        return $_class;
    }
}