<?php
defined( 'ABSPATH' ) || exit;

// Pagenavi
function wpcom_pagination( $range = 9, $args = array() ) {
    global $paged, $wp_query, $page, $numpages, $multipage;

    if ( ($args && $args['numpages'] > 1) || ( isset($multipage) && $multipage && is_single() ) ) {
        if($args) {
            $page = isset($args['paged']) ? $args['paged'] : $page;
            $numpages = isset($args['numpages']) ? $args['numpages'] : $numpages;
        }
        echo ' <div class="pagination clearfix">';
        $prev = $page - 1;
        if ( $prev > 0 ) {
            echo str_replace('<a', '<a class="prev"', wpcom_link_page( $prev, $args ) . __('&laquo; Previous', 'wpcom') . '</a>');
        }

        if($numpages > $range){
            if($page < $range){
                for($i = 1; $i <= ($range + 1); $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', wpcom_link_page($i, $args)) . $i . "</a>";
                    } else {
                        echo wpcom_link_page($i, $args) . $i . "</a>";
                    }
                }
            } elseif($page >= ($numpages - ceil(($range/2)))){
                for($i = $numpages - $range; $i <= $numpages; $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', wpcom_link_page($i, $args)) . $i . "</a>";
                    } else {
                        echo wpcom_link_page($i, $args) . $i . "</a>";
                    }
                }
            } elseif($page >= $range && $page < ($numpages - ceil(($range/2)))){
                for($i = ($page - ceil($range/2)); $i <= ($page + ceil(($range/2))); $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', wpcom_link_page($i, $args)) . $i . "</a>";
                    } else {
                        echo wpcom_link_page($i, $args) . $i . "</a>";
                    }
                }
            }
        }else{
            for ( $i = 1; $i <= $numpages; $i++ ) {
                if($i==$page){
                    echo str_replace('<a', '<a class="current"', wpcom_link_page($i, $args)) . $i . "</a>";
                } else {
                    echo wpcom_link_page($i, $args) . $i . "</a>";
                }
            }
        }

        $next = $page + 1;
        if ( $next <= $numpages ) {
            echo str_replace('<a', '<a class="next"', wpcom_link_page( $next, $args ) . __('Next &raquo;', 'wpcom') . '</a>');
        }
        echo '</div>';
    }else if( ($max_page = $wp_query->max_num_pages) > 1 ){
        echo ' <div class="pagination clearfix">';
        if(!$paged) $paged = 1;
        echo '<span>'.$paged.' / '.$max_page.'</span>';
        previous_posts_link(__('&laquo; Previous', 'wpcom'));
        if($max_page > $range){
            if($paged < $range){
                for($i = 1; $i <= ($range + 1); $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            } elseif($paged >= ($max_page - ceil(($range/2)))){
                for($i = $max_page - $range; $i <= $max_page; $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            } elseif($paged >= $range && $paged < ($max_page - ceil(($range/2)))){
                for($i = ($paged - ceil($range/2)); $i <= ($paged + ceil(($range/2))); $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            }
        } else {
            for($i = 1; $i <= $max_page; $i++){
                echo "<a href='" . get_pagenum_link($i) ."'";
                if($i==$paged) echo " class='current'";
                echo ">$i</a>";
            }
        }
        next_posts_link(__('Next &raquo;', 'wpcom'));
        if($max_page>$range) echo '<form class="pagination-go" method="get"><input class="pgo-input" type="text" name="paged" placeholder="'._x('Paged', '页码', 'wpcom').'" /><button class="pgo-btn" type="submit">' . WPCOM::icon('arrow-circle-right', false) . '</button></form>';
        echo '</div>';
    }
}

function wpcom_link_page( $i, $args ) {
    if(isset($args['url']) && $args['url']){
        if ( '' == get_option( 'permalink_structure' ) ) {
            $url = add_query_arg( isset($args['paged_arg']) && $args['paged_arg'] ? $args['paged_arg'] : 'page', $i, $args['url'] );
        } else {
            $url = trailingslashit( $args['url'] ) . user_trailingslashit( $i, 'single_paged' );
        }
        $url = '<a href="' . esc_url( $url ) . '" class="post-page-numbers">';
    }else{
        $url = _wp_link_page($i);
    }
    return $url;
}

add_filter('previous_posts_link_attributes', 'wpcom_prev_posts_link_attr');
function wpcom_prev_posts_link_attr($attr){
    return $attr.' class="prev"';
}
add_filter('next_posts_link_attributes', 'wpcom_next_posts_link_attr');
function wpcom_next_posts_link_attr($attr){
    return $attr.' class="next"';
}