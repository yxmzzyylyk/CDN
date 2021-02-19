<ul class="messages-list">
    <?php if( is_array($list) && $list) {
        foreach ($list as $item) {
            // print_r($item);
            $msger = get_user_by('ID', $item->group_user);
            $item->content = strip_tags(preg_replace('/<img [^>]+>/i', '[图片]', $item->content));
            ?>
            <li class="messages-item j-message" data-user="<?php echo $item->group_user;?>">
                <div class="messages-item-avatar">
                    <?php echo get_avatar($msger->ID);?>
                </div>
                <div class="messages-item-content">
                    <div class="messages-item-title">
                        <span class="messages-item-time"><?php echo get_date_from_gmt($item->time, 'Y-m-d H:i');?></span>
                        <h4 class="messages-item-name"><?php echo $msger->display_name;?></h4>
                    </div>
                    <div class="messages-item-text">
                        <?php echo $item->content;
                        if($item->unread) echo '<span class="messages-item-unread">'.$item->unread.'</span>'; ?>
                    </div>
                </div>
            </li>
    <?php } }else{ ?>
        <li class="messages-emtpy"><?php echo wpcom_empty_icon();?>暂未发送或收到私信</li>
    <?php } ?>
</ul>
<?php if($pages>1){ wpcom_pagination(5, array('numpages' => $pages, 'paged' => $paged, 'url' => wpcom_subpage_url('messages'), 'paged_arg' => 'pageid')); } ?>