<?php
defined('_ASTEXE_') or die('No access');
?>
<div class="col-lg-12">
    <div class="hpanel">
        <div class="panel-body">
            <h3>Причины и статусы</h3>
        </div>
    </div>
</div>
<div class="col-lg-6">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Список причин для возврата
        </div>
        <div class="panel-body">
            <div id="reasons">

            </div>
            <div class="block_add_setting">
                <div class="content_reason" onclick="add_reason_block()">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-lg-6">
    <div class="hpanel">
        <div class="panel-heading hbuilt">
            Список статусов заявок на возврат
        </div>
        <div class="panel-body">
            <div id="statuses">
            </div>
            <div class="block_add_setting">
                <div class="content_reason" onclick="add_status_block()">
                    <i class="fas fa-plus"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let main_url = "<?php echo $DP_Config->domain_path;?><?=$DP_Config->backend_dir;?>";
    let tech_key = "<?=$DP_Config->tech_key;?>";
    let csrf_guard_key = '<?php echo $user_session["csrf_guard_key"]; ?>';

</script>

<script src="<?php echo $DP_Config->domain_path . $DP_Config->backend_dir;?>/content/shop/returns/assets/reasons_statuses.js"></script>
<link rel="stylesheet" href="<?=$DP_Config->domain_path . $DP_Config->backend_dir;?>/content/shop/returns/assets/reasons_statuses.css">

