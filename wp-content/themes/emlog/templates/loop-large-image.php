<?php
global $options, $is_author, $is_sticky, $post;
$show_author = isset($options['show_author']) && $options['show_author']=='0' ? 0 : 1;
$is_multimage = is_multimage();
?>
    <li class="item item4<?php echo $is_sticky&&is_sticky()?' item-sticky':'';?>">
        <div class="item-content<?php echo isset($is_author) && $is_author && (current_user_can('edit_published_posts') || $post->post_status =='draft' || $post->post_status =='pending' ) ? ' item-edit' : '';?>">
            <?php if(isset($is_author) && $is_author && (current_user_can('edit_published_posts') || $post->post_status =='draft' || $post->post_status =='pending' )){?>
                <a class="edit-link" href="<?php echo get_edit_link($post->ID);?>" target="_blank">编辑</a>
            <?php } ?>
            <h2 class="item-title">
                <a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>"<?php echo wpcom_post_target();?>>
                    <?php if(isset($is_author) && $post->post_status=='draft'){ echo '<span>【草稿】</span>'; }else if(isset($is_author) && $post->post_status=='pending'){ echo '<span>【待审核】</span>'; }?>
                    <?php if($is_sticky&&is_sticky()){ ?><span class="sticky-post">置顶</span><?php } ?> <?php the_title();?>
                </a>
            </h2>
            <?php
            $thumb = $is_multimage!=3 ? WPCOM::thumbnail_url($post->ID, 'full') : '';
            if($is_multimage==3 || !$thumb){
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', get_the_content(), $matches);
                if(isset($matches[1])) $imgs = array_slice($matches[1], 0, 5);
                if(isset($imgs) && isset($imgs[0])) $thumb = $imgs[0];
            }
            if($is_multimage==3 && isset($imgs) && isset($imgs[1])){ ?>
            <div class="item-image">
                <div class="item-slider wpcom-slider swiper-container">
                    <ul class="swiper-wrapper">
                        <?php foreach($imgs as $img){ ?>
                            <li class="swiper-slide"><div <?php echo wpcom_lazybg($img, 'item-image-el');?>></div></li>
                        <?php } ?>
                    </ul>
                    <div class="swiper-pagination"></div>
                    <!-- Add Navigation -->
                    <div class="swiper-button-prev swiper-button-white"></div>
                    <div class="swiper-button-next swiper-button-white"></div>
                </div>
            </div>
            <?php } else if($thumb) { ?>
                <a class="item-image" href="<?php the_permalink();?>"<?php echo wpcom_post_target();?>>
                    <div <?php echo wpcom_lazybg($thumb, 'item-image-el');?>></div>
                </a>
            <?php } ?>
            <div class="item-excerpt">
                <?php the_excerpt(); ?>
            </div>
            <div class="item-meta">
                <?php if( $show_author && isset($options['member_enable']) && $options['member_enable']=='1' ){ ?>
                    <div class="item-meta-li author">
                        <?php
                        $author = get_the_author_meta( 'ID' );
                        $author_url = get_author_posts_url( $author );
                        ?>
                        <a data-user="<?php echo $author;?>" target="_blank" href="<?php echo $author_url; ?>" class="avatar j-user-card">
                            <?php echo get_avatar( $author, 60, '',  get_the_author());?>
                            <span><?php echo get_the_author(); ?></span>
                        </a>
                    </div>
                <?php } ?>
                <?php
                $category = get_the_category();
                $cat = $category?$category[0]:'';
                if($cat){ ?>
                    <a class="item-meta-li" href="<?php echo get_category_link($cat->cat_ID);?>" target="_blank"><?php echo $cat->name;?></a>
                <?php } ?>
                <span class="item-meta-li date"><?php echo format_date(get_post_time( 'U', false, $post ));?></span>

                <?php
                $post_metas = isset($options['post_metas']) && is_array($options['post_metas']) ? $options['post_metas'] : array();
                foreach ( $post_metas as $meta ) echo wpcom_post_metas($meta);
                ?>
            </div>
        </div>
    </li>
<?php do_action('wpcom_echo_ad', 'ad_flow');?>