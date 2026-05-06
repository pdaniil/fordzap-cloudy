<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Действия
        </div>
        <div class="panel-body">
            <a class="panel_a" onClick="<?= $_GET["id"] > 0 ? "updatePost(".$_GET["id"].");" : "createPost();";?>" href="javascript:void(0);">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/save.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Сохранить</div>
            </a>

            <?php if ($_GET["id"] > 0) : ?>
            <a class="panel_a" onClick="deletePostById(<?=$_GET["id"];?>);" href="javascript:void(0);">
                <div class="panel_a_img" style="background: url('/<?php echo $DP_Config->backend_dir; ?>/templates/<?php echo $DP_Template->name; ?>/images/content_delete.png') 0 0 no-repeat;"></div>
                <div class="panel_a_caption">Удалить</div>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Пост
        </div>
        <div class="panel-body">
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Название*
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="text" id="post_caption" value="<?=$post_data["caption"];?>">
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
            <div class="form-group">
                <label for="" class="col-lg-6 control-label">
                    Изображение
                </label>
                <div class="col-lg-6">
                    <input class="form-control" type="hidden" id="post_image_input" value="<?=$post_data["image"];?>">
                    <div class="icon_wrapper">
                        <img class="post_image" src="<?=$post_data["image"];?>" alt="" onerror="this.src='/content/files/images/no_image.png'" onclick="showIconsList()">
                        <div class="icons_list hidden">
                            <?php foreach ($arr_images as $image_src) : ?>
                                <img onclick="changeCurrentImage('<?=$image_src;?>')" class="icon" src="<?=$image_src?>" alt="">
                            <?php endforeach; ?>
                            <div class="close_button" onclick="hideIconList()">
                                <div class="close_image"><img src="../../content/car_service/files/icons/cross.png" alt=""></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="hr-line-dashed col-lg-12"></div>
        </div>
    </div>
</div>