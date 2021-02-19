<?php defined( 'ABSPATH' ) || exit;?>
<ul class="wpcom-user-list user-cols-<?php echo $cols;?>">
    <?php foreach ( $users as $user ){
        $cover_photo = wpcom_get_cover_url( $user->ID ); ?>
        <li class="wpcom-user-item">
            <?php echo $this->load_template('user-card', array('user' => $user));?>
        </li>
    <?php } ?>
</ul>