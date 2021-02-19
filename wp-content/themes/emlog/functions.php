<?php

define( 'THEME_ID', '5b4220be66895b87' ); // 主题ID，请勿修改！！！
define( 'THEME_VERSION', '5.7.3' ); // 主题版本号，请勿修改！！！

// Themer 框架路径信息常量，请勿修改，框架会用到
define( 'FRAMEWORK_PATH', is_dir($framework_path = get_template_directory() . '/themer') ? $framework_path : get_theme_root() . '/Themer/themer' );
define( 'FRAMEWORK_URI', is_dir($framework_path) ? get_template_directory_uri() . '/themer' : get_theme_root_uri() . '/Themer/themer' );

require FRAMEWORK_PATH . '/load.php';

function add_menu(){
    return array(
        'primary'   => '导航菜单',
        'footer'   => '页脚菜单'
    );
}
add_filter('wpcom_menus', 'add_menu');

// sidebar
if ( ! function_exists( 'wpcom_widgets_init' ) ) :
    function wpcom_widgets_init() {
        register_sidebar( array(
            'name'          => '首页边栏',
            'id'            => 'home',
            'description'   => '用户首页显示的边栏',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>'
        ) );
    }
endif;
add_action( 'wpcom_sidebar', 'wpcom_widgets_init' );

add_filter('wpcom_image_sizes', 'justnews_image_sizes', 20);
function justnews_image_sizes($image_sizes){
    $image_sizes['post-thumbnail'] = array(
        'width' => 480,
        'height' => 300
    );
    return $image_sizes;
}

// Excerpt length
if ( ! function_exists( 'wpcom_excerpt_length' ) ) :
    function wpcom_excerpt_length() {
        global $options;
        return isset($options['excerpt_len']) && $options['excerpt_len'] ? $options['excerpt_len'] : 180;
    }
endif;
add_filter( 'excerpt_length', 'wpcom_excerpt_length', 999 );

function format_date($time){
    global $options, $post;
    $p_id = isset($post->ID) ? $post->ID : 0;
    $q_id = get_queried_object_id();
    $single = $p_id == $q_id && is_single();
    if(isset($options['time_format']) && $options['time_format']=='0'){
        return date(get_option('date_format').($single?' '.get_option('time_format'):''), $time);
    }
    $t = current_time('timestamp') - $time;
    $f = array(
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    if($t==0){
        return '1秒前';
    }else if( $t >= 604800 || $t < 0){
        return date(get_option('date_format').($single?' '.get_option('time_format'):''), $time);
    }else{
        foreach ($f as $k=>$v)    {
            if (0 !=$c=floor($t/(int)$k)) {
                return $c.$v.'前';
            }
        }
    }
}

add_action('wp_ajax_wpcom_like_it', 'wpcom_like_it');
add_action('wp_ajax_nopriv_wpcom_like_it', 'wpcom_like_it');
function wpcom_like_it(){
    $data = $_POST;
    $res = array();
    if(isset($data['id']) && $data['id'] && $post = get_post($data['id'])){
        $cookie = isset($_COOKIE["wpcom_liked_".$data['id']])?$_COOKIE["wpcom_liked_".$data['id']]:0;
        if(isset($cookie) && $cookie=='1'){
            $res['result'] = -2;
        }else{
            $res['result'] = 0;
            $likes = get_post_meta($data['id'], 'wpcom_likes', true);
            $likes = $likes ? $likes : 0;
            $res['likes'] = $likes + 1;
            // 数据库增加一个喜欢数量
            update_post_meta( $data['id'], 'wpcom_likes', $res['likes'] );
            //cookie标记已经给本文点赞过了
            setcookie('wpcom_liked_'.$data['id'], 1, time()+3600*24*365, '/');
        }
    }else{
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_action('wp_ajax_wpcom_heart_it', 'wpcom_heart_it');
add_action('wp_ajax_nopriv_wpcom_heart_it', 'wpcom_heart_it');
function wpcom_heart_it(){
    $data = $_POST;
    $res = array();
    $current_user = wp_get_current_user();
    if($current_user->ID){
        if(isset($data['id']) && $data['id'] && $post = get_post($data['id'])){
            // 用户关注的文章
            $u_favorites = get_user_meta($current_user->ID, 'wpcom_favorites', true);
            $u_favorites = $u_favorites ? $u_favorites : array();
            // 文章关注人数
            $p_favorite = get_post_meta($data['id'], 'wpcom_favorites', true);
            $p_favorite = $p_favorite ? $p_favorite : 0;
            if(in_array($data['id'], $u_favorites)){ // 用户是否关注本文
                $res['result'] = 1;
                $nu_favorites = array();
                foreach($u_favorites as $uf){
                    if($uf != $data['id']){
                        $nu_favorites[] = $uf;
                    }
                }
                $p_favorite -= 1;
            }else{
                $res['result'] = 0;
                $u_favorites[] = $data['id'];
                $nu_favorites = $u_favorites;
                $p_favorite += 1;
            }
            $p_favorite = $p_favorite<0 ? 0 : $p_favorite;
            update_user_meta($current_user->ID, 'wpcom_favorites', $nu_favorites);
            update_post_meta($data['id'], 'wpcom_favorites', $p_favorite);
            $res['favorites'] = $p_favorite;
        }else{
            $res['result'] = -2;
        }
    }else{ // 未登录
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_filter( 'wpcom_profile_tabs_posts_class', 'justnews_profile_posts_class' );
function justnews_profile_posts_class(){
    return 'profile-posts-list post-loop post-loop-default clearfix';
}

add_filter( 'wpcom_profile_tabs', 'wpcom_add_profile_tabs' );
function wpcom_add_profile_tabs( $tabs ){
    global $options, $current_user, $profile;
    $tabs += array(
        30 => array(
            'slug' => 'favorites',
            'title' => __( 'Favorites', 'wpcom' )
        )
    );

    if( isset($current_user->ID) && isset($profile->ID) && $profile->ID === $current_user->ID && isset($options['tougao_on']) && $options['tougao_on']=='1') {
        $tabs += array(
            40 => array(
                'slug' => 'addpost',
                'title' => __('Add post', 'wpcom')
            )
        );
    }

    return $tabs;
}

add_action('wpcom_profile_tabs_favorites', 'wpcom_favorites');
function wpcom_favorites() {
    global $profile, $post;
    $favorites = get_user_meta($profile->ID, 'wpcom_favorites', true);

    $empty_icon = wpcom_empty_icon();

    if($favorites) {
        add_filter('posts_orderby', 'favorites_posts_orderby');
        $args = array(
            'post_type' => 'post',
            'post__in' => $favorites,
            'posts_per_page' => get_option('posts_per_page'),
            'ignore_sticky_posts' => 1
        );
        $posts = new WP_Query($args);
        if ( $posts->have_posts() ) {
            echo '<ul class="profile-posts-list profile-favorites-list post-loop post-loop-default clearfix" data-user="'.$profile->ID.'">';
            while ($posts->have_posts()) : $posts->the_post();
                get_template_part('templates/loop', 'default');
            endwhile;
            echo '</ul>';
            if ($posts->max_num_pages > 1) { ?>
                <div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-favorites"><?php _e('Load more posts', 'wpcom'); ?></a></div><?php }
        } else {
            if (get_current_user_id() == $profile->ID) {
                echo '<div class="profile-no-content">' . $empty_icon . __('You have no favorite posts.', 'wpcom') . '</span></div>';
            } else {
                echo '<div class="profile-no-content">' . $empty_icon . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
            }
        }
        wp_reset_query();
    }else{
        if( get_current_user_id()==$profile->ID ) {
            echo '<div class="profile-no-content">' . $empty_icon . __('You have no favorite posts.', 'wpcom') . '</span></div>';
        } else {
            echo '<div class="profile-no-content">' . $empty_icon . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
        }
    }
}

add_action( 'wp_ajax_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
add_action( 'wp_ajax_nopriv_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
function wpcom_profile_tabs_favorites(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        $favorites = get_user_meta($user->ID, 'wpcom_favorites', true);

        if($favorites) {
            add_filter('posts_orderby', 'favorites_posts_orderby');

            $per_page = get_option('posts_per_page');
            $page = $_POST['page'];
            $page = $page ? $page : 1;
            $arg = array(
                'post_type' => 'post',
                'posts_per_page' => $per_page,
                'post__in' => $favorites,
                'paged' => $page,
                'ignore_sticky_posts' => 1
            );
            $posts = new WP_Query($arg);

            if ($posts->have_posts()) {
                while ($posts->have_posts()) : $posts->the_post();
                    get_template_part('templates/loop', 'default');
                endwhile;
                wp_reset_postdata();
            } else {
                echo 0;
            }
        }
    }
    exit;
}

function favorites_posts_orderby( $orderby ){
    global $wpdb, $profile;
    if( !isset($profile) ) return $orderby;

    $favorites = get_user_meta( $profile->ID, 'wpcom_favorites', true );
    if($favorites) $orderby = "FIELD(".$wpdb->posts.".ID, ".implode(',', $favorites).") DESC";

    return $orderby;
}

add_filter( 'wpcom_profile_tab_url', 'add_post_tab_link', 10, 3 );
function add_post_tab_link( $tab_html, $tab, $url ){
    if( $tab['slug'] == 'addpost' ){
        $tab_html = '<a target="_blank" href="' . wpcom_addpost_url() . '">'.$tab['title'].'</a>';
    }
    return $tab_html;
}

function wpcom_addpost_url(){
    global $options;
    if( isset($options['tougao_page']) && $options['tougao_page'] ){
        return get_permalink( $options['tougao_page'] );
    }
}

function post_editor_settings($args = array()){
    $img = current_user_can('upload_files');
    return array(
        'textarea_name' => $args['textarea_name'],
        'media_buttons' => false,
        'quicktags' => false,
        'tinymce'       => array(
            'height'        => 350,
            'content_css' => get_template_directory_uri() . '/css/editor-style.css',
            'toolbar1' => 'formatselect,bold,underline,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,'.($img?'wpcomimg,':'image,').'undo,redo,fullscreen,wp_help',
            'toolbar2' => '',
            'toolbar3' => '',
            'external_plugins' => '{wpcomimg: "' . get_template_directory_uri() . '/js/edit-img.js"}'
        )
    );
}

add_filter( 'mce_external_plugins', 'wpcom_mce_plugin');
function wpcom_mce_plugin($plugin_array){
    global $is_submit_page;
    if ( $is_submit_page ) {
        wp_enqueue_media();
        wp_enqueue_script('jquery.taghandler', get_template_directory_uri() . '/js/jquery.taghandler.min.js', array('jquery'), THEME_VERSION, true);
        wp_enqueue_script('edit-post', get_template_directory_uri() . '/js/edit-post.js', array('jquery'), THEME_VERSION, true);
    }
    return $plugin_array;
}

add_action('pre_get_posts','wpcom_restrict_media_library');
function wpcom_restrict_media_library( $wp_query_obj ) {
    global $current_user, $pagenow;
    if( ! $current_user instanceof WP_User )
        return;
    if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
        return;
    if( !current_user_can('edit_others_posts') )
        $wp_query_obj->set('author', $current_user->ID );
    return;
}

function wpcom_tougao_tinymce_style($content) {
    if ( ! is_admin() ) {
        global $editor_styles, $stylesheet;
        $editor_styles = (array) $editor_styles;
        $stylesheet    = (array) $stylesheet;
        $stylesheet[] = 'css/editor-style.css';
        $editor_styles = array_merge( $editor_styles, $stylesheet );
    }
    return $content;
}

add_filter('wpcom_update_post','wpcom_update_post');
function wpcom_update_post($res){

    add_filter('the_editor_content', "wpcom_tougao_tinymce_style");

    if(isset($_POST['post-title'])){ // 只处理post请求
        $nonce = $_POST['wpcom_update_post_nonce'];
        if ( wp_verify_nonce( $nonce, 'wpcom_update_post' ) ){
            $post_id = isset($_GET['post_id'])?$_GET['post_id']:'';

            $post_title = $_POST['post-title'];
            $post_excerpt = $_POST['post-excerpt'];
            $post_content = $_POST['post-content'];
            $post_category = isset($_POST['post-category'])?$_POST['post-category']:array();
            $post_tags = $_POST['post-tags'];
            $_thumbnail_id = $_POST['_thumbnail_id'];

            if($post_id){ // 编辑文章
                $post = get_post($post_id);
                if(isset($post->ID)) { // 文章要存在
                    $p = array(
                        'ID' => $post_id,
                        'post_type' => 'post',
                        'post_title' => $post_title,
                        'post_excerpt' => $post_excerpt,
                        'post_content' => $post_content,
                        'post_category' => $post_category,
                        'tags_input' => $post_tags
                    );
                    if($post->post_status=='draft' && trim($post_title)!='' && trim($post_content)!=''){
                        $p['post_status'] = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                    }
                    $pid = wp_update_post($p, true);
                    if ( !is_wp_error( $pid ) ) {
                        update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    }
                }
            }else{ // 新建文章
                if(trim($post_title)=='' && trim($post_content)==''){
                    return array();
                }else if(trim($post_title)=='' || trim($post_content)=='' || empty($post_category)){
                    $post_status = 'draft';
                }else{
                    $post_status = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                }
                $p = array(
                    'post_type' => 'post',
                    'post_title' => $post_title,
                    'post_excerpt' => $post_excerpt,
                    'post_content' => $post_content,
                    'post_status' => $post_status,
                    'post_category' => $post_category,
                    'tags_input' => $post_tags
                );
                $pid = wp_insert_post($p, true);
                if ( !is_wp_error( $pid ) ) {
                    update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    update_post_meta($pid, 'wpcom_copyright_type', 'copyright_tougao');
                    wp_redirect(get_edit_link($pid).'&submit=true');
                }
            }
        }
    }
    return $res;
}

function get_edit_link($id){
    $url = wpcom_addpost_url();
    $url =  add_query_arg( 'post_id', $id, $url );
    return $url;
}

add_action('wp_ajax_wpcom_load_posts', 'wpcom_load_posts');
add_action('wp_ajax_nopriv_wpcom_load_posts', 'wpcom_load_posts');
function wpcom_load_posts(){
    global $is_sticky, $wp_posts;
    $is_sticky = 1;
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $page = isset($_POST['page']) ? $_POST['page'] : '';
    $page = $page ? $page : 1;
    $type = isset($_POST['type']) ? $_POST['type'] : 'default';
    $per_page = isset($_POST['per_page']) ? $_POST['per_page'] : get_option('posts_per_page');
    if($id){
        $wp_posts = new WP_Query(array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'cat' => $id,
            'post_type' => 'post',
            'post_status' => array( 'publish' ),
            'ignore_sticky_posts' => 0
        ));
    }else{
        $exclude = isset($_POST['exclude']) ? $_POST['exclude'] : '';
        if($exclude) $exclude = explode(',', $exclude);
        $exclude = $exclude ? $exclude : array();
        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'ignore_sticky_posts' => 0,
            'post_type' => 'post',
            'post_status' => array( 'publish' ),
            'category__not_in' => $exclude
        );
        $wp_posts = new WP_Query($arg);
    }
    if($wp_posts->have_posts()) {
        while ( $wp_posts->have_posts() ) : $wp_posts->the_post();
            get_template_part('templates/loop', $type);
        endwhile;
        wp_reset_postdata();
        if($id && $page==1 && $wp_posts->max_num_pages>1){
            echo '<li class="load-more-wrap"><a class="load-more j-load-more" data-id="'.$id.'" href="javascript:;">'.__('Load more posts', 'wpcom').'</a></li>';
        }
    }else{
        echo 0;
    }
    exit;
}

add_action( 'init', 'wpcom_create_special' );
function wpcom_create_special(){
    global $options, $pagenow, $wp_version;
    if(!isset($options['special_on']) || $options['special_on']=='1') { //是否开启专题功能
        $slug = isset($options['special_slug']) && $options['special_slug'] ? $options['special_slug'] : 'special';
        $labels = array(
            'name' => '专题',
            'singular_name' => '专题',
            'search_items' => '搜索专题',
            'all_items' => '所有专题',
            'parent_item' => '父级专题',
            'parent_item_colon' => '父级专题',
            'edit_item' => '编辑专题',
            'update_item' => '更新专题',
            'add_new_item' => '添加专题',
            'new_item_name' => '新专题名',
            'not_found' => '暂无专题',
            'menu_name' => '专题',
        );
        $is_hierarchical = $pagenow === 'edit.php' || ($pagenow === 'admin-ajax.php' && isset($_POST['action']) && $_POST['action'] === 'inline-save');
        $args = array(
            'hierarchical' => $is_hierarchical || version_compare($wp_version, '5.1', '<') ? true : false,
            'meta_box_cb' => 'post_categories_meta_box',
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $slug),
            'show_in_rest' => true
        );
        register_taxonomy('special', 'post', $args);
    }
}

add_filter('rest_prepare_taxonomy', 'wpcom_prepare_special', 10, 3);
function wpcom_prepare_special( $response, $taxonomy, $request ){
    $context = ! empty( $request['context'] ) ? $request['context'] : 'view';
    if( $context === 'edit' && $taxonomy->name == 'special' && $taxonomy->hierarchical === false ){
        $data_response = $response->get_data();
        $data_response['hierarchical'] = true;
        $response->set_data( $data_response );
    }
    return $response;
}

function get_special_list($num=10, $paged=1){
    $special = get_terms( array(
        'taxonomy' => 'special',
        'orderby' => 'id',
        'order' => 'DESC',
        'number' => $num,
        'hide_empty' => false,
        'offset' => $num*($paged-1)
    ) );
    return $special;
}

// 优化专题排序支持 Simple Custom Post Order 插件
add_filter( 'get_terms_orderby', 'wpcom_get_terms_orderby', 20, 3 );
function wpcom_get_terms_orderby($orderby, $args, $tax){
    if(class_exists('SCPO_Engine') && $tax && count($tax)==1 && $tax[0]=='special'){
        $orderby = 't.term_order, t.term_id';
    }
    return $orderby;
}

add_action('wp_ajax_wpcom_load_special', 'wpcom_load_special');
add_action('wp_ajax_nopriv_wpcom_load_special', 'wpcom_load_special');
function wpcom_load_special(){
    global $options, $post;
    $page = $_POST['page'];
    $page = $page ? $page : 1;
    $per_page = isset($options['special_per_page']) && $options['special_per_page'] ? $options['special_per_page'] : 10;

    $special = get_special_list($per_page, $page);
    if($special){
    foreach($special as $sp){
        $thumb = get_term_meta( $sp->term_id, 'wpcom_thumb', true );
        $link = get_term_link($sp->term_id);
        ?>
        <div class="col-md-6 col-xs-12 special-item-wrap">
            <div class="special-item">
                <div class="special-item-top">
                    <div class="special-item-thumb">
                        <a href="<?php echo $link;?>" target="_blank"><img src="<?php echo esc_url($thumb);?>" alt="<?php echo esc_attr($sp->name);?>"></a>
                    </div>
                    <div class="special-item-title">
                        <h2><a href="<?php echo $link;?>" target="_blank"><?php echo $sp->name;?></a></h2>
                        <?php echo category_description($sp->term_id);?>
                    </div>
                    <a class="special-item-more" href="<?php echo $link;?>"><?php echo _x('Read More', 'topic', 'wpcom');?></a>
                </div>
                <ul class="special-item-bottom">
                    <?php
                    $args = array(
                        'posts_per_page' => 3,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'special',
                                'field' => 'term_id',
                                'terms' => $sp->term_id
                            )
                        )
                    );
                    $postslist = get_posts( $args );
                    foreach($postslist as $post) {
                        setup_postdata($post);?>
                        <li><a title="<?php echo esc_attr(get_the_title());?>" href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a></li>
                    <?php } wp_reset_postdata(); ?>
                </ul>
            </div>
        </div>
    <?php }
    } else {
        echo 0;
    }
    exit;
}

function wpcom_post_copyright() {
    global $post, $options;
    $copyright = '';

    $copyright_type = get_post_meta($post->ID, 'wpcom_copyright_type', true);
    if(!$copyright_type){
        $copyright = isset($options['copyright_default']) ? $options['copyright_default'] : '';
    }else if($copyright_type=='copyright_tougao'){
        $copyright = isset($options['copyright_tougao']) ? $options['copyright_tougao'] : '';;
    }else if($copyright_type){
        if(isset($options['copyright_id']) && $options['copyright_id']) {
            foreach ($options['copyright_id'] as $i => $id) {
                if($copyright_type == $id && $options['copyright_text'][$i]) {
                    $copyright = $options['copyright_text'][$i];
                }
            }
        }
    }

    if(preg_match('%SITE_NAME%', $copyright)) $copyright = str_replace('%SITE_NAME%', get_bloginfo('name'), $copyright);
    if(preg_match('%SITE_URL%', $copyright)) $copyright = str_replace('%SITE_URL%', get_bloginfo('url'), $copyright);
    if(preg_match('%POST_TITLE%', $copyright)) $copyright = str_replace('%POST_TITLE%', get_the_title(), $copyright);
    if(preg_match('%POST_URL%', $copyright)) $copyright = str_replace('%POST_URL%', get_permalink(), $copyright);
    if(preg_match('%AUTHOR_NAME%', $copyright)) $copyright = str_replace('%AUTHOR_NAME%', get_the_author(), $copyright);
    if(preg_match('%AUTHOR_URL%', $copyright)) $copyright = str_replace('%AUTHOR_URL%', get_author_posts_url(get_the_author_meta( 'ID' )), $copyright);
    if(preg_match('%ORIGINAL_NAME%', $copyright)) $copyright = str_replace('%ORIGINAL_NAME%', get_post_meta($post->ID, 'wpcom_original_name', true), $copyright);
    if(preg_match('%ORIGINAL_URL%', $copyright)) $copyright = str_replace('%ORIGINAL_URL%', get_post_meta($post->ID, 'wpcom_original_url', true), $copyright);

    echo $copyright ? '<div class="entry-copyright">'.$copyright.'</div>' : '';
}

add_filter('comment_reply_link', 'wpcom_comment_reply_link', 10, 1);
function wpcom_comment_reply_link($link){
    if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
        $link = '<a rel="nofollow" class="comment-reply-login" href="javascript:;">回复</a>';
    }
    return $link;
}

add_action('init', 'wpcom_allow_contributor_uploads');
function wpcom_allow_contributor_uploads() {
    $user = wp_get_current_user();
    if( isset($user->roles) && $user->roles && $user->roles[0] == 'contributor' ){
        global $options;
        $allow = isset($options['tougao_upload']) && $options['tougao_upload']=='0' ? 0 : 1;
        $can_upload = isset($user->allcaps['upload_files']) ? $user->allcaps['upload_files'] : 0;

        if ( $allow && !$can_upload ) {
            $contributor = get_role('contributor');
            $contributor->add_cap('upload_files');
        } else if(!$allow && $can_upload){
            $contributor = get_role('contributor');
            $contributor->remove_cap('upload_files');
        }
    }
}

add_theme_support( 'wc-product-gallery-lightbox' );

add_action( 'wpcom_echo_ad', 'wpcom_echo_ad', 10, 1);
function wpcom_echo_ad( $id ){
    if($id && $id=='ad_flow'){
        global $wp_query, $wp_posts;
        $query = isset($wp_posts) && isset($wp_posts->post_count) ? $wp_posts : $wp_query;
        if(!isset($query->ad_index)) $query->ad_index = rand(1, $query->post_count-2);
        $current_post = $query->current_post;
        if(isset($query->posts->current_post)) $current_post = $query->posts->current_post;
        if($current_post==$query->ad_index && $current_post>0) echo wpcom_ad_html($id);
    }else if($id) {
        echo wpcom_ad_html($id);
    }
}

function wpcom_ad_html($id){
    if($id) {
        global $options;
        $html = '';
        if( wp_is_mobile() && isset($options[$id.'_mobile']) && $options[$id.'_mobile']!=='' ) {
            if(trim($options[$id.'_mobile'])){
                $html = '<div class="wpcom_ad_wrap">';
                $html .= $options[$id.'_mobile'];
                $html .= '</div>';
            }
        } else if ( isset($options[$id]) && $options[$id] ) {
            $html = '<div class="wpcom_ad_wrap">';
            $html .= $options[$id];
            $html .= '</div>';
        }

        if($html && $id=='ad_flow') $html = '<li class="item item-ad">'.$html.'</li>';
        return $html;
    }
}

add_action( 'wp_head', 'wpcom_style_output', 20 );
if ( ! function_exists( 'wpcom_style_output' ) ) :
    function wpcom_style_output(){
        global $options;
        if(!isset($options['theme_color'])) return false; ?>
        <style>
            <?php
            $theme_color = WPCOM::color($options['theme_color']?$options['theme_color']:'#3ca5f6');
            $theme_color_hover = WPCOM::color($options['theme_color_hover']?$options['theme_color_hover']:'#4285f4');
            $sticky_color = isset($options['sticky_color'])?$options['sticky_color']:'';
            $action_color = isset($options['action_color']) && $options['action_color'] ? $options['action_color'] : '';
            if( $theme_color!='#3ca5f6' || $theme_color_hover!='#4285f4' ){
                include get_template_directory() . '/css/color.php';
                if( function_exists('is_woocommerce') ) include get_template_directory() . '/css/woo-color.php';
            }
            if(isset($options['bg_color']) && ($options['bg_color'] || $options['bg_image'])){ ?>@media (min-width: 992px){
                body{  <?php if($options['bg_color']) {echo 'background-color: '.WPCOM::color($options['bg_color']).';';};?> <?php if($options['bg_image']) {echo 'background-image: url('.$options['bg_image'].');';};?><?php if($options['bg_image_repeat']) {echo 'background-repeat: '.$options['bg_image_repeat'].';';};?><?php if(isset($options['bg_image_size']) && $options['bg_image_size'] && (!$options['bg_image_repeat']||$options['bg_image_repeat']=='no-repeat')) {echo 'background-size: 100% auto;'.($options['bg_image_size']==2?'background-size:cover;':'').'';};?><?php if($options['bg_image_position']) {echo 'background-position: '.$options['bg_image_position'].';';};?><?php if($options['bg_image_attachment']=='1') {echo 'background-attachment: fixed;';};?>}
                <?php if($options['special_title_color']){?>.special-head .special-title,.special-head p{color:<?php echo WPCOM::color($options['special_title_color']);?>;}.special-head .page-description:before{background:<?php echo WPCOM::color($options['special_title_color']);?>;}<?php } ?>
                .special-head .page-description:before,.special-head p{opacity: 0.5;}
            }<?php } if( isset($options['member_login_bg']) && $options['member_login_bg'] !='' ) { ?>
            .page-no-sidebar.member-login #wrap,.page-no-sidebar.member-register #wrap{ background-image: url('<?php echo esc_url($options['member_login_bg']);?>');}
            <?php } ?>
            <?php
            if($action_color) {?>.action.action-color-1 .action-item{background-color: <?php echo $action_color;?>;}<?php }
            $header_bg = isset($options['header_bg']) && $options['header_bg'] ? $options['header_bg'] : '';
            if($header_bg){ ?>
            body>header.header{<?php echo WPCOM::gradient_color($header_bg);?>;}
            <?php }
            if(isset($options['logo-height']) && $logo_height = intval($options['logo-height'])){
            $logo_height = $logo_height>50 ? 50 : $logo_height;
            ?>
            body>header.header .logo img{max-height: <?php echo $logo_height;?>px;}
            <?php } if(isset($options['logo-height-mobile']) && $mob_logo_height = intval($options['logo-height-mobile'])){
            $mob_logo_height = $mob_logo_height>40 ? 40 : $mob_logo_height;
            ?>
            @media (max-width: 767px){
                body>header.header .logo img{max-height: <?php echo $mob_logo_height;?>px;}
            }
            <?php }
            $video_height = intval(isset($options['post_video_height']) && $options['post_video_height'] ? $options['post_video_height'] : 482);?>
            .entry .entry-video{ height: <?php echo $video_height ?>px;}
            @media (max-width: 1219px){
                .entry .entry-video{ height: <?php echo $video_height * (688/858) ?>px;}
            }
            @media (max-width: 991px){
                .entry .entry-video{ height: <?php echo $video_height * (800/858) ?>px;}
            }
            @media (max-width: 767px){
                .entry .entry-video{ height: <?php echo $video_height/1.4 ?>px;}
            }
            @media (max-width: 500px){
                .entry .entry-video{ height: <?php echo $video_height/2 ?>px;}
            }
            <?php if(get_locale()!='zh_CN'){ ?>
            .action .a-box:hover:after{padding: 0;font-family: "FontAwesome";font-size: 20px;line-height: 40px;}
            .action .contact:hover:after{content:'\f0e5';}
            .action .wechat:hover:after{content:'\f029';}
            .action .share:hover:after{content:'\f045';}
            .action .gotop:hover:after{content:'\f106';font-size: 36px;}
            <?php }
            if($sticky_color){ ?>
            @media screen and (-webkit-min-device-pixel-ratio: 0) {
                .post-loop .item-sticky .item-title a{-webkit-background-clip: text;-webkit-text-fill-color: transparent;}
                .post-loop .item-sticky .item-title a, .post-loop .item-sticky .item-title a .sticky-post,.post-loop-card .item-sticky .item-title .sticky-post{
                    <?php echo WPCOM::gradient_color($sticky_color);?>
                }
            }
            <?php }
            if(is_single()||is_page()){
                global $post;
                $em = isset($options['show_indent'])?$options['show_indent']:get_post_meta($post->ID, 'wpcom_show_indent', true);
                if($em=='1'){
                    echo '.entry-content p{text-indent: 2em;}';
                }
            }
            echo $options['custom_css'];?>
        </style>
    <?php }
endif;

function is_multimage( $post_id = '' ){
    global $post, $options;
    if($post_id==''){
        $post_id = $post->ID;
    }
    $multimage = get_post_meta($post_id, 'wpcom_multimage', true);
    $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;
    return $multimage;
}

add_action('init', 'wpcom_kx_init');
if ( ! function_exists( 'wpcom_kx_init' ) ) :
    function wpcom_kx_init(){
        global $options;
        if(isset($options['kx_on']) && $options['kx_on']=='1') {
            $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
            $labels = array(
                'name' => '快讯',
                'singular_name' => '快讯',
                'add_new' => '添加',
                'add_new_item' => '添加',
                'edit_item' => '编辑',
                'new_item' => '添加',
                'view_item' => '查看',
                'search_items' => '查找',
                'not_found' => '没有内容',
                'not_found_in_trash' => '回收站为空',
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'capability_type' => 'page',
                'hierarchical' => true,
                'menu_position' => null,
                'rewrite' => array('slug' => $slug),
                'show_in_rest' => true,
                'supports' => array('title', 'excerpt', 'thumbnail', 'comments')
            );
            register_post_type('kuaixun', $args);

            // add post meta
            add_filter( 'wpcom_post_metas', 'wpcom_add_kx_metas' );
        }
    }
endif;

add_action( 'pre_get_posts', 'wpcom_kx_orderby' );
function wpcom_kx_orderby( $query ){
    if( function_exists('get_current_screen') && $query->is_admin ) {
        $screen = get_current_screen();
        if ( isset($screen->base) && isset($screen->post_type) && 'edit' == $screen->base && 'kuaixun' == $screen->post_type && !isset($_GET['orderby'])) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
}

if ( ! function_exists( 'wpcom_add_kx_metas' ) ) :
    function wpcom_add_kx_metas( $metas ){
        $metas['kuaixun'] = array(
            array(
                "title" => "快讯设置",
                "option" => array(
                    array(
                        'name' => 'kx_url',
                        'title' => '快讯来源',
                        'desc' => '快讯来源链接地址',
                        'type' => 'text'
                    )
                )
            )
        );
        return $metas;
    }
endif;

add_filter( 'get_the_excerpt', 'wpcom_kx_excerpt', 20, 2 );
if ( ! function_exists( 'wpcom_kx_excerpt' ) ) :
    function wpcom_kx_excerpt( $excerpt, $post ) {
        if( $post->post_type == 'kuaixun' && $url = get_post_meta($post->ID, 'wpcom_kx_url', true ) ){
            $excerpt .= ' <a class="kx-more" href="'.esc_url($url).'" target="_blank" rel="nofollow">[原文链接]</a>';
        }
        return $excerpt;
    }
endif;

add_action( 'init', 'wpcom_kx_rewrite' );
function wpcom_kx_rewrite() {
    global $wp_rewrite, $options, $permalink_structure;
    if(isset($options['kx_on']) && $options['kx_on']=='1') {
        if (!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure) {
            $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
            $queryarg = 'post_type=kuaixun&p=';
            $wp_rewrite->add_rewrite_tag('%kx_id%', '([^/]+)', $queryarg);
            $wp_rewrite->add_permastruct('kuaixun', $slug . '/%kx_id%.html', false);
        }
    }
}

add_filter('post_type_link', 'wpcom_kx_permalink', 5, 2);
function wpcom_kx_permalink( $post_link, $id ) {
    global $wp_rewrite, $permalink_structure, $options;
    if(isset($options['kx_on']) && $options['kx_on']=='1') {
        if (!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        if ($permalink_structure) {
            $post = get_post($id);
            if (!is_wp_error($post) && $post->post_type == 'kuaixun') {
                $newlink = $wp_rewrite->get_extra_permastruct('kuaixun');
                $newlink = str_replace('%kx_id%', $post->ID, $newlink);
                $newlink = home_url(untrailingslashit($newlink));
                return $newlink;
            }
        }
    }
    return $post_link;
}

add_action('wp_ajax_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
add_action('wp_ajax_nopriv_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
if ( ! function_exists( 'wpcom_load_kuaixun' ) ) :
    function wpcom_load_kuaixun(){
        global $options;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $per_page = get_option('posts_per_page');

        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun'
        );
        $posts = new WP_Query($arg);

        if($posts->have_posts()) {
            $cur_day = '';
            while ( $posts->have_posts() ) : $posts->the_post();
                if($cur_day != $date = get_the_date(get_option('date_format'))){
                    $cur_day = $date;
                    $pre_day = '';
                    $week = date_i18n(get_the_date('l'));
                    if(date(get_option('date_format'), current_time('timestamp')) == $date) {
                        $pre_day = '今天 • ';
                    }else if(date(get_option('date_format'), current_time('timestamp')-86400) == $date){
                        $pre_day = '昨天 • ';
                    }else if(date(get_option('date_format'), current_time('timestamp')-86400*2) == $date){
                        $pre_day = '前天 • ';
                    }
                    echo '<div class="kx-date">'. $pre_day .$date . ' • ' . $week.'</div>';
                } ?>
                <div class="kx-item" data-id="<?php the_ID();?>">
                    <span class="kx-time"><?php the_time('H:i');?></span>
                    <div class="kx-content">
                        <h2><?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a>
                            <?php } else{ the_title(); } ?></h2>
                        <?php the_excerpt();?>
                        <?php if(get_the_post_thumbnail()){ ?>
                            <?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a class="kx-img" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_post_thumbnail('full'); ?></a>
                            <?php }else{ ?>
                                <div class="kx-img"><?php the_post_thumbnail('full'); ?></div>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <div class="kx-meta clearfix" data-url="<?php the_permalink();?>">
                        <span class="j-mobile-share" data-id="<?php the_ID();?>" data-qrcode="<?php the_permalink();?>">
                            <?php WPCOM::icon('share-alt');?> <?php _e('Generate poster', 'wpcom');?>
                        </span>
                        <span class="hidden-xs"><?php _e('Share to: ', 'wpcom');?></span>
                        <?php if(isset($options['post_shares'])){ if($options['post_shares']){ foreach ($options['post_shares'] as $share){ ?>
                            <a class="share-icon <?php echo $share;?> hidden-xs" target="_blank" data-share="<?php echo $share;?>" data-share-callback="kx_share">
                                <?php WPCOM::icon($share);?>
                            </a>
                        <?php } } }else{ ?>
                            <a class="share-icon wechat hidden-xs" data-share="wechat" data-share-callback="kx_share"><?php WPCOM::icon('wechat');?></a>
                            <a class="share-icon weibo hidden-xs" target="_blank" data-share="weibo" data-share-callback="kx_share"><?php WPCOM::icon('weibo');?></a>
                            <a class="share-icon qq hidden-xs" target="_blank" data-share="qq" data-share-callback="kx_share"><?php WPCOM::icon('qq');?></a>
                        <?php } ?>
                        <a class="share-icon copy hidden-xs"><?php WPCOM::icon('file-text');?></a>
                    </div>
                </div>
            <?php endwhile;
            wp_reset_postdata();
        }else{
            echo 0;
        }
        exit;
    }
endif;

add_action('wp_ajax_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
add_action('wp_ajax_nopriv_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
function wpcom_new_kuaixun(){
    $id = isset($_POST['id']) && $_POST['id'] ? $_POST['id'] : '';
    if($post = get_post($id)){
        $time = get_the_time('U', $post->ID);
        $args = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun',
            'date_query' => array(
                array(
                    'after'    => array(
                        'year'   => date('Y', $time),
                        'month'  => date('m', $time),
                        'day'    => date('d', $time),
                        'hour'   => date('H', $time),
                        'minute' => date('i', $time),
                        'second' => date('s', $time),
                    ),
                    'inclusive' => false
                )
            ),
            'posts_per_page' => -1,
        );
        $my_date_query = new WP_Query( $args );
        echo $my_date_query->found_posts;
    }
    exit;
}

add_action('wp_loaded', 'wpcom_tinymce_replace_start');
if ( ! function_exists( 'wpcom_tinymce_replace_start' ) ) {
    function wpcom_tinymce_replace_start() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            ob_start("wpcom_tinymce_replace_url");
        }
    }
}

add_action('shutdown', 'wpcom_tinymce_replace_end');
if ( ! function_exists( 'wpcom_tinymce_replace_end' ) ) {
    function wpcom_tinymce_replace_end() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            if (ob_get_level() > 0) ob_end_flush();
        }
    }
}

if ( ! function_exists( 'wpcom_tinymce_replace_url' ) ) {
    function wpcom_tinymce_replace_url( $str ){
        $regexp = "/\/wp-includes\/js\/tinymce/i";
        $path = get_template_directory_uri();
        $path = str_replace(get_option( 'siteurl' ), '', $path);
        $str = preg_replace( $regexp, $path . '/js/tinymce', $str );
        $str = preg_replace( '/tinymce\.Env\.ie \< 11/i', 'tinymce.Env.ie < 8', $str );
        $str = preg_replace( '/wp-editor-wrap html-active/i', 'wp-editor-wrap tmce-active', $str );
        return $str;
    }
}

add_filter( 'user_can_richedit', 'wpcom_can_richedit' );
if ( ! function_exists( 'wpcom_can_richedit' ) ) {
    function wpcom_can_richedit( $wp_rich_edit ){
        global $is_IE;
        if( !$wp_rich_edit && $is_IE && !is_admin() ){
            $wp_rich_edit = 1;
        }
        return $wp_rich_edit;
    }
}

function wpcom_post_metas( $key = '', $url = true ){
    $html = '';
    if($key){
        global $post;
        switch ($key){
            case 'h':
                $fav = get_post_meta($post->ID, 'wpcom_favorites', true);
                $fav = $fav ? $fav : 0;
                $html = '<span class="item-meta-li hearts" title="喜欢数">' . WPCOM::icon('heart', false) . ' '.$fav.'</span>';
                break;
            case 'z':
                $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                $likes = $likes ? $likes : 0;
                $html = '<span class="item-meta-li likes" title="点赞数">' . WPCOM::icon('thumbs-up', false) . ' '.$likes.'</span>';
                break;
            case 'v':
                if( function_exists('the_views') ) {
                    $views = $post->views ? $post->views : 0;
                    if ($views >= 1000) $views = sprintf("%.1f", $views / 1000) . 'K';
                    $html = '<span class="item-meta-li views" title="阅读数">' . WPCOM::icon('eye', false) . ' ' . $views . '</span>';
                }
                break;
            case 'c':
                $comments = get_comments_number();
                if($url){
                    $html = '<a class="item-meta-li comments" href="'.get_permalink($post->ID).'#comments" target="_blank" title="评论数">';
                }else{
                    $html = '<span class="item-meta-li comments" title="评论数">';
                }
                $html .= WPCOM::icon('comments', false) . ' ' . $comments;
                $html .= $url ? '</a>' : '</span>';
                break;
        }
    }
    return $html;
}

add_shortcode('wpcom_tags', 'wpcom_shortcode_tags');
function wpcom_shortcode_tags($args){
    $paged = ( get_query_var( 'page' ) ) ? get_query_var( 'page' ) : 1;
    $number = isset($args['per_page']) && $args['per_page'] ? $args['per_page'] : 0;
    $offset = ( $paged > 0 ) ?  $number * ( $paged - 1 ) : '';
    $max   = wp_count_terms( 'post_tag', array( 'hide_empty' => true ) );
    $totalpages   = $number ? ceil( $max / $number ) : 0;
    $args = array(
        'taxonomy' => 'post_tag',
        'orderby' => 'count',
        'order' => 'DESC',
        'offset' => $offset,
        'number' => $number
    );
    $tags = get_terms('post_tag', $args);
    if ( empty( $tags ) || is_wp_error( $tags ) ) {
        return;
    }
    $html = '<ul class="wpcom-shortcode-tags">';
    foreach ( $tags as $key => $tag ) {
        $link = get_term_link( intval( $tag->term_id ), $tag->taxonomy );
        if ( is_wp_error( $link ) ) {
            return;
        }
        $html .= '<li><a href="'.$link.'" target="_blank" title="'.($tag->description?$tag->description:$tag->name).'">'.$tag->name.'</a><span>('.$tag->count.')</span></li>';
    }
    $html .= '</ul>';
    if($number){
        ob_start();
        wpcom_pagination(6, array('paged' => $paged, 'numpages' => $totalpages));
        $html .= ob_get_contents();
        ob_end_clean();
    }
    return $html;
}

add_filter( 'wpcom_localize_script', 'wpcom_video_height' );
function wpcom_video_height($scripts){
    global $options;
    $scripts['video_height'] = $video_height = intval(isset($options['post_video_height']) && $options['post_video_height'] ? $options['post_video_height'] : 482);;
    return $scripts;
}

add_filter( 'body_class', 'wpcom_el_boxed_class' );
function wpcom_el_boxed_class($class) {
    global $options;
    if( !isset($options['el_boxed']) || (isset($options['el_boxed']) && $options['el_boxed']) ) $class[] = 'el-boxed';
    return $class;
}

add_filter( 'wpcom_thumbnail_url', 'wpcom_thumbnail_url', 10, 4);
function wpcom_thumbnail_url($img_url, $post_id, $post_thumbnail_id, $size){
    global $options;
    $_post = $post_id ? get_post($post_id) : '';
    if(!$post_thumbnail_id && !$img_url && isset($_post->ID) && $_post->post_type == 'post'){
        $img_id = isset($options['post_thumb']) && $options['post_thumb'] ? $options['post_thumb'] : '';
        if($img_id) $img_url = wp_get_attachment_image_url( $img_id, $size );
    }
    return $img_url;
}

add_action('embed_head', 'wpcom_embed_head');
function wpcom_embed_head() {
    $css = is_child_theme() ? '/style.css' : '/css/style.css';
    wp_enqueue_style('stylesheet', get_stylesheet_directory_uri() . $css, array(), THEME_VERSION);
}

remove_action('embed_footer', 'print_embed_sharing_dialog');

add_filter('wpcom_exclude_post_metas', 'wpcom_exclude_post_metas');
function wpcom_exclude_post_metas($metas) {
    $metas += array('favorites', 'likes');
    return $metas;
}

add_action('wp_enqueue_scripts', 'wpcom_theme_scripts', 1);
function wpcom_theme_scripts() {
    wp_deregister_script('wp-embed');
    wp_register_script('wp-embed', get_template_directory_uri() . '/js/wp-embed.js', array('jquery'), THEME_VERSION);
}

// 新旧版本配置信息兼容处理
add_filter( 'option_izt_theme_options', 'wpcom_update_theme_options' );
function wpcom_update_theme_options( $value ){
    if(!$value) return $value;
    if($value && is_string($value)) $value = json_decode($value, true);
    if(isset($value['tongji']) && $value['tongji']) {
        $value['footer_code'] = $value['tongji'];
        unset($value['tongji']);
    }
    if(isset($value['header_bg2']) && $value['header_bg2']) {
        $value['header_bg'] = '{"c1":"'.$value['header_bg'].'","c2":"'.$value['header_bg2'].'","d":0}';
        unset($value['header_bg2']);
    }
    if(isset($value['sticky_color1']) && $value['sticky_color1']) {
        $value['sticky_color'] = $value['sticky_color1'];
        unset($value['sticky_color1']);
    }
    if(isset($value['sticky_color']) && $value['sticky_color'] && isset($value['sticky_color2']) && $value['sticky_color2']) {
        $value['sticky_color'] = '{"c1":"'.$value['sticky_color'].'","c2":"'.$value['sticky_color2'].'","d":0}';
        unset($value['sticky_color2']);
    }
    if(isset($value['footer_bar_target']) && $value['footer_bar_target']){
        foreach ($value['footer_bar_target'] as $i => $target){
            if($target && $value['footer_bar_url'] && $value['footer_bar_url'][$i] && $value['footer_bar_type'][$i]=='0')
                $value['footer_bar_url'][$i] = $value['footer_bar_url'][$i] . ', _blank';
        }
        unset($value['footer_bar_target']);
    }
    if(isset($value['kl_newwindow']) && $value['kl_newwindow']){
        foreach ($value['kl_newwindow'] as $i => $new){
            if($new && $value['kl_link'] && $value['kl_link'][$i]) $value['kl_link'][$i] = $value['kl_link'][$i] . ', _blank';
        }
        unset($value['kl_newwindow']);
    }
    if(isset($value['kl_nofollow']) && $value['kl_nofollow']){
        foreach ($value['kl_nofollow'] as $i => $nof){
            if($nof && $value['kl_link'] && $value['kl_link'][$i]) $value['kl_link'][$i] = $value['kl_link'][$i] . ', nofollow';
        }
        unset($value['kl_nofollow']);
    }
    return $value;
}

function wpcom_post_target(){
    global $options;
    return isset($options['post_target']) && $options['post_target']==='' ? '' : ' target="_blank"';
}

add_filter('wpcom_localize_script', 'wpcom_login_register_url');
function wpcom_login_register_url($scripts){
    if(!is_user_logged_in()){
        $scripts['login_url'] = wp_login_url();
        $scripts['register_url'] = wp_registration_url();
    }
    return $scripts;
}
/**
 * WordPress 后台禁用Google Open Sans字体，加速网站
 * https://www.wpdaxue.com/disable-google-fonts.html
 */
add_filter( 'gettext_with_context', 'wpdx_disable_open_sans', 888, 4 );
function wpdx_disable_open_sans( $translations, $text, $context, $domain ) {
  if ( 'Open Sans font: on or off' == $context && 'on' == $text ) {
    $translations = 'off';
  }
  return $translations;
}
//关闭rss feed功能
function disable_all_feeds() {
wp_die(__('<h1>本博客不提供Feed，请访问网站<a href="'.get_bloginfo('url').'">首页</a>！</h1>'));
}
add_action('do_feed', 'disable_all_feeds', 1);
add_action('do_feed_rdf', 'disable_all_feeds', 1);
add_action('do_feed_rss', 'disable_all_feeds', 1);
add_action('do_feed_rss2', 'disable_all_feeds', 1);
add_action('do_feed_atom', 'disable_all_feeds', 1);
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );
//后台
function login_protection(){
if ($_GET['q123']!='Mkjd03eQ1')header('Location: https://www.pxr0.com');
}
add_action('login_enqueue_scripts','login_protection');
//禁止前端加载样式文件
remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
//禁用古腾堡编辑器
add_filter('use_block_editor_for_post', '__return_false');
//屏蔽古腾堡的样式加载
remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
//添加下载按钮
function appthemes_add_quicktags() {
?><script type="text/javascript">// <![CDATA[
 
QTags.addButton( 'xydowns', '下载按钮', '<div class="sg-dl"><span class="sg-dl-span"><a href="https://www.pxr0.com/download.php?id=','" target=_blank title="文件下载" rel="nofollow noopener noreferrer"><button type="button" class="btn-download">下载地址点这里</button></a></span></div><!--wechatfans end-->' );
// ]]></script><?php } add_action('admin_print_footer_scripts', 'appthemes_add_quicktags' );
//禁止wlwmanifest.xml/ rsd显示
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
/** 图片自动添加ALT和TITLE */
function image_alt_title($content){
    global $post;preg_match_all('/<img (.*?)\/>/', $content, $images);
    if(!is_null($images)) {foreach($images[1] as $index => $value)
    {
        $new_img = str_replace('<img', '<img alt="'.get_the_title().'-'.get_bloginfo('name').'"'.'title="'.get_the_title().'-'.get_bloginfo('name').'"', $images[0][$index]);
        $content = str_replace($images[0][$index], $new_img, $content);}}
        return $content;
}
add_filter('the_content', 'image_alt_title', 99999);
/**
 * Enqueue scripts for all admin pages.
 * https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/.
 */
function wpmore_admin_script()
{
    $script =array('jquery','wp-polyfill','react','react-dom','utils','wplink','shortcode','underscore','clipboard','hoverIntent');
 
    foreach ($script as $value) {
      wp_deregister_script($value);
    }
 
    wp_register_script('jquery', 'https://cdn.jsdelivr.net/npm/jquery@1.12.4/dist/jquery.min.js', array(), '1.12.4', true);
 
    wp_register_script('wp-polyfill', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/dist/vendor/wp-polyfill.min.js', array(), 'master', true);
 
    wp_register_script('react', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/dist/vendor/react.min.js', array(), 'master', true);
 
    wp_register_script('react-dom', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/dist/vendor/react-dom.min.js', array(), 'master', true);
 
    wp_register_script('utils', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/utils.min.js', array(), 'master', true);
 
    wp_register_script('wplink', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/wplink.min.js', array(), 'master', true);
 
    wp_register_script('shortcode', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/shortcode.min.js', array(), 'master', true);
 
    wp_register_script('underscore', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/underscore.min.js', array(), 'master', true);
 
    wp_register_script('clipboard', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/clipboard.min.js', array(), 'master', true);
 
    wp_register_script('hoverIntent', 'https://cdn.jsdelivr.net/gh/WordPress/WordPress@master/wp-includes/js/hoverIntent.min.js', array(), 'master', true);
 
    foreach ($script as $value) {
      wp_enqueue_script($value);
    }
}
add_action('admin_enqueue_scripts', 'wpmore_admin_script');