<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_fullwidth extends WPCOM_Module {
    function __construct() {
        $options = array(
            array(
                'tab-name' => '常规设置',
                'fluid' => array(
                    'name' => '固定宽度',
                    'type' => 't',
                    'desc' => '模块内容宽度固定，居中显示；否则内容宽度不固定，为100%',
                    'value'  => '1'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'bg-color' => array(
                    'name' => '背景颜色',
                    'type' => 'c',
                    'gradient' => 1
                ),
                'bg-video' => array(
                    'name' => '背景视频',
                    'type' => 'u',
                    'desc' => '可选，MP4格式视频，另外由于手机端无法自动播放，所以为兼容手机端建议再设置背景图片选项'
                ),
                'bg-image' => array(
                    'name' => '背景图片',
                    'type' => 'u',
                ),
                'wrap' => array(
                    'filter' => 'bg-image:!!!',
                    'type' => 'wrapper',
                    'o' => array(
                        'bg-image-repeat' => array(
                            'name' => '背景平铺',
                            'type' => 'r',
                            'ux' => 1,
                            'value'  => 'no-repeat',
                            'o' => array(
                                'no-repeat' => '不平铺',
                                'repeat' => '平铺',
                                'repeat-x' => '水平平铺',
                                'repeat-y' => '垂直平铺'
                            )
                        ),
                        'bg-image-size' => array(
                            'name' => '背景铺满',
                            'type' => 'r',
                            'ux' => 1,
                            'f' => 'bg-image-repeat:no-repeat',
                            'desc' => '自动调整背景图片显示',
                            'value'  => '1',
                            'o' => array(
                                '0' => '不使用',
                                '1' => '铺满模块',
                                '2' => '按宽度铺满'
                            )
                        ),
                        'bg-image-position' => array(
                            'name' => '背景位置',
                            'type' => 's',
                            'desc' => '分别为左右对齐方式和上下对齐方式',
                            'value'  => 'center center',
                            'o' => array(
                                'left top' => '左 上',
                                'left center' => '左 中',
                                'left bottom' => '左 下',
                                'center top' => '中 上',
                                'center center' => '中 中',
                                'center bottom' => '中 下',
                                'right top' => '右 上',
                                'right center' => '右 中',
                                'right bottom' => '右 下',
                            )
                        ),
                        'bg-image-attachment' => array(
                            'name' => '背景固定',
                            'type' => 't',
                            'desc' => '背景图片固定，不跟随滚动，若开启则需要确保图片高度足够'
                        ),
                        'bg-image-shadow' => array(
                            'name' => '背景处理',
                            'type' => 'r',
                            'ux' => 1,
                            'desc' => '优化处理背景图片',
                            'value'  => '0',
                            'o' => array(
                                '0' => '不处理',
                                '1' => '暗化处理',
                                '2' => '亮化处理'
                            )
                        )
                    )
                ),
                'margin' => array(
                    'name' => '外边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'use' => 'tb',
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => '20px'
                ),
                'padding' => array(
                    'name' => '内边距',
                    'type' => 'trbl',
                    'mobile' => 1,
                    'desc' => '模块内容区域与边界的距离',
                    'units' => 'px, %',
                    'value'  => '20px 0'
                )
            )
        );
        parent::__construct( 'fullwidth', '全宽模块', $options, 'mti:add_to_queue' );
    }

    function classes($atts, $depth){
        $classes = 'j-modules-wrap';
        if($this->value('bg-image')) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $classes .= ' j-lazy';
            }
        }
        return $classes;
    }

    function style($atts){
        return array(
            'bg-color' => array(
                '' => WPCOM::gradient_color($this->value('bg-color'))
            ),
            'bg-image-shadow' => array(
                '' => $this->value('bg-image-shadow') ? 'position: relative;' : ''
            ),
            'bg-video' => array(
                '' => $this->value('bg-video') ? 'position: relative;' : ''
            ),
            'bg-image-repeat' => array(
                '' => 'background-repeat: {{value}};'
            ),
            'bg-image-size' => array(
                '' => $this->value('bg-image-repeat')==='no-repeat' && $this->value('bg-image-size') ? ('background-size: ' . ($this->value('bg-image-size')=='1' ? 'cover' : '100% auto') . ';') : ''
            ),
            'bg-image-position' => array(
                '' => 'background-position: {{value}};'
            ),
            'bg-image-attachment' => array(
                '' => $this->value('bg-image-attachment') ? 'background-attachment: fixed;-webkit-backface-visibility: hidden;' : ''
            )
        );
    }

    function style_inline($atts){
        $style = '';
        if($this->value('bg-image')) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
                $style .= 'background-image: url('.$lazy_img.');';
            }else{
                $style .= 'background-image: url('.$this->value('bg-image').');';
            }
        }
        return $style;
    }

    function _style_inline( $atts ){
        $style = '';
        $style_inline = $this->style_inline( $atts );
        if($style_inline) $style = 'style="'.$style_inline.'"';
        if($this->value('bg-image')) {
            global $options;
            if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
                $style .= ' data-original="'.$this->value('bg-image').'"';
            }
        }
        return $style;
    }

    function template($atts, $depth) { ?>
        <?php if($this->value('bg-video')) {
            $video_class = 'module-bg-video';
            if($this->value('bg-image-attachment')=='1') $video_class .= ' module-bg-fixed';
            ?>
        <div class="<?php echo esc_attr($video_class);?>">
            <video muted autoplay loop playsinline preload="auto" src="<?php echo esc_url($this->value('bg-video'));?>"></video>
        </div>
        <?php } if($this->value('bg-image-shadow')=='1'){?><div class="module-shadow"></div><?php } ?>
        <?php if($this->value('bg-image-shadow')=='2'){?><div class="module-shadow module-shadow-white"></div><?php } ?>
        <div class="j-modules-inner container<?php echo $this->value('fluid')?'':'-fluid';?>"<?php echo $this->value('bg-image-shadow') ? ' style="position: relative;"':''; ?>>
            <?php if($this->value('modules')){ foreach ($this->value('modules') as $module) {
                $module['settings']['modules-id'] = $module['id'];
                $module['settings']['parent-id'] = $this->value('modules-id');
                $module['settings']['fullwidth'] = $this->value('fluid') ? 0 : 1;
                do_action('wpcom_modules_' . $module['type'], $module['settings'], $depth+1);
            } } ?>
        </div>
    <?php }
}

register_module( 'WPCOM_Module_fullwidth' );