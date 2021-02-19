<?php
if(!defined('ABSPATH')){
    return;
}
if($list){
?>
<table class="wbs-table">
    <thead>
    <tr>
        <th>访问时间</th>
        <th>蜘蛛IP</th>
        <th>蜘蛛</th>
    </tr>
    </thead>
    <body>
    <?php foreach($list as $r){?>
    <tr>
        <td><?php echo $r->visit_date;?></td>
        <td><?php echo $r->visit_ip;?></td>
        <td><?php echo $r->spider;?></td>
    </tr>
    <?php } ?>
    </body>
</table>
<?php } else{?>
<div class="empty-tips-bar">
    <span>- 暂无数据 -</span>
</div>
<?php } ?>