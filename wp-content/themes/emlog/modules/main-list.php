<?php
class WPCOM_Module_main_list extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'latest-title' => array(
                    'name' => '默认Tab标题',
                    'desc' => '第一个默认Tab标题显示文案',
                    'value'  => '最新文章'
                ),
                'exclude' => array(
                    'name' => '排除分类',
                    'type' => 'cat-multi',
                    'desc' => '文章列表排除的分类，排除分类的文章将不显示在最新文章列表'
                ),
                'cats' => array(
                    'name' => 'Tab切换分类',
                    'type' => 'cat-multi-sort',
                    'desc' => '列表切换栏展示的文章分类，按勾选顺序排序'
                ),
                'type' => array(
                    'name' => '显示方式',
                    'type' => 's',
                    'o' => array(
                        '' => '默认列表',
                        'list' => '文章列表',
                        'image' => '图片列表',
                        'card' => '卡片列表',
                    )
                ),
                'cols' => array(
                    'name' => '每行显示',
                    'type' => 'r',
                    'ux' => 1,
                    'value'  => '3',
                    'filter' => 'type:image,type:card',
                    'o' => array(
                        '2' => '2篇',
                        '3' => '3篇',
                        '4' => '4篇',
                        '5' => '5篇'
                    )
                ),
                'per_page' => array(
                    'name' => '显示数量',
                    'desc' => '分页加载每页显示数量'
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
        parent::__construct('main-list', '文章主列表', $options, 'mti:view_list');
    }

    function template( $atts, $depth ){
        global $is_sticky;
        $is_sticky = 1;
        $cats = $this->value('cats', array());
        $type = $this->value('type', 'default');
        $per_page = $this->value('per_page', get_option('posts_per_page'));
        ?>
        <div class="sec-panel main-list main-list-<?php echo $type;?>">
            <div class="sec-panel-head">
                <ul class="list tabs j-newslist" data-type="<?php echo $type;?>" data-per_page="<?php echo $per_page;?>">
                    <li class="tab active">
                        <a data-id="0" href="javascript:;">
                            <?php echo $this->value('latest-title', __('Latest Posts', 'wpcom'));?>
                        </a>
                    </li>
                    <?php if($cats){ foreach($cats as $cat){ ?>
                        <li class="tab"><a data-id="<?php echo $cat;?>" href="javascript:;"><?php echo get_cat_name($cat);?></a></li>
                    <?php } } ?>
                </ul>
            </div>
            <ul class="post-loop post-loop-<?php echo $type;?> cols-<?php echo $this->value('cols');?> tab-wrap clearfix active">
                <?php
                $exclude = $this->value('exclude', array());
                $arg = array(
                    'posts_per_page' => $per_page,
                    'ignore_sticky_posts' => 0,
                    'post_type' => 'post',
                    'post_status' => array( 'publish' ),
                    'category__not_in' => $exclude
                );
                global $wp_posts;
                $wp_posts = new WP_Query($arg);
                if( $wp_posts->have_posts() ) { while ( $wp_posts->have_posts() ) { $wp_posts->the_post(); ?>
                    <?php get_template_part( 'templates/loop' , $type ); ?>
                <?php } } wp_reset_postdata(); ?>
                <?php if($wp_posts->max_num_pages>1){ ?>
                    <li class="load-more-wrap">
                        <a class="load-more j-load-more" href="javascript:;" data-exclude="<?php echo empty($exclude) ? '' : implode(',', $exclude);?>"><?php _e('Load more posts', 'wpcom');?></a>
                    </li>
                <?php } ?>
            </ul>
            <?php if($cats){ foreach($cats as $cat){ ?>
                <ul class="post-loop post-loop-<?php echo $type;?> cols-<?php echo $this->value('cols');?> tab-wrap clearfix"></ul>
            <?php } } ?>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_main_list' );