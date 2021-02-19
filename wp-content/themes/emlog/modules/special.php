<?php
class WPCOM_Module_special extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '模块标题',
                ),
                'sub-title' => array(
                    'name' => '副标题'
                ),
                'more-title' => array(
                    'name' => '更多专题标题'
                ),
                'more-url' => array(
                    'type' => 'url',
                    'name' => '更多专题链接'
                ),
                'special' => array(
                    'name' => '显示专题',
                    'type' => 'cat-multi-sort',
                    'tax' => 'special',
                    'desc' => '选择需要展示的专题，按勾选顺序排序'
                )
            ),
            array(
                'tab-name' => '风格样式',
                'margin' => array(
                    'name' => '外边距',
                    'type' => 'trbl',
                    'use' => 'tb',
                    'mobile' => 1,
                    'desc' => '和上下模块/元素的间距',
                    'units' => 'px, %',
                    'value'  => '20px'
                )
            )
        );
        parent::__construct('special', '专题展示', $options, 'mti:library_books');
    }

    function template( $atts, $depth ){ ?>
        <div class="sec-panel topic-recommend">
            <?php if( $this->value('title') ){ ?>
                <div class="sec-panel-head">
                    <h3>
                        <span><?php echo $this->value('title');?></span> <small><?php echo $this->value('sub-title');?></small>
                        <?php if($this->value('more-url') && $this->value('more-title')){ ?><a class="more" <?php echo WPCOM::url($this->value('more-url'));?>><?php echo $this->value('more-title');?></a><?php } ?>
                    </h3>
                </div>
            <?php } ?>
            <div class="sec-panel-body">
                <ul class="list topic-list">
                    <?php if($this->value('special')){ foreach($this->value('special') as $sp){
                        $term = get_term($sp, 'special');
                        if(isset($term->term_id) && $term->term_id){
                            $thumb = get_term_meta( $term->term_id, 'wpcom_thumb', true ); ?>
                            <li class="topic">
                                <a class="topic-wrap" href="<?php echo get_term_link($term->term_id);?>" target="_blank">
                                    <div class="cover-container">
                                        <?php echo wpcom_lazyimg($thumb, $term->name);?>
                                    </div>
                                    <span><?php echo $term->name;?></span>
                                </a>
                            </li>
                        <?php } } }?>
                </ul>
            </div>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_special' );