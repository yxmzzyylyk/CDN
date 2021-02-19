<?php
$cover_photo = wpcom_get_cover_url( $user->ID );
$display_name = apply_filters('wpcom_user_display_name', '<span class="display-name">' . $user->display_name . '</span>', $user->ID, 'full');
?>
<div class="user-card-header">
    <div class="user-card-cover" style="background-image: url(<?php echo esc_url($cover_photo);?>"></div>
    <a class="user-card-avatar" href="<?php echo get_author_posts_url( $user->ID );?>" target="_blank">
        <?php echo get_avatar( $user->ID, 60 );?>
    </a>
    <a class="user-card-name" href="<?php echo get_author_posts_url( $user->ID );?>" target="_blank"><?php echo $display_name;?></a>
    <p class="user-card-desc"><?php echo $user->description;?></p>
</div>
<div class="user-card-stats">
    <?php do_action('wpcom_user_data_stats', $user->ID);?>
</div>
<div class="user-card-action">
    <?php do_action('wpcom_user_card_action', $user->ID);?>
</div>