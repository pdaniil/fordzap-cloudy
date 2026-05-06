<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Действия
        </div>
        <div class="panel-body">
            <a class="panel_a" href="/<?php echo $DP_Config->backend_dir; ?>/car_service/service_post?id=0">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_add.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Добавить</div>
            </a>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Таблица постов
        </div>
        <div class="panel-body">
            <?php if (count($post_list_arr) > 0) : ?>
            <div class="table-responsive">
                <table cellpadding="1" cellspacing="1" class="table table-condensed table-striped">
                    <thead>
                    <tr>
                        <th></th>
                        <th>ID</th>
                        <th>Название</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($post_list_arr as $post) :
                    $a_item = '<a href="'.$DP_Config->domain_path.$DP_Config->backend_dir.'/car_service/service_post?id=' . $post["id"] . '">';
                    ?>
                    <tr>
                        <td></td>
                        <td><?= $a_item .$post["id"];?></a></td>
                        <td><?=$a_item.$post["caption"];?></a></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else : ?>
            Для создания поста нажмите кнопку "Добавить".
            <?php endif; ?>
        </div>
    </div>
</div>