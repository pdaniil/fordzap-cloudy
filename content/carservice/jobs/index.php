<?php 
ini_set("display_errors", 1);
require_once($_SERVER["DOCUMENT_ROOT"]."/config.php");
$DP_Config = new DP_Config;

try
{
    $db_link = new PDO('mysql:host='.$DP_Config->host.';dbname='.$DP_Config->db, $DP_Config->user, $DP_Config->password);
}
catch (PDOException $e) 
{
    $result = array();
    $result["status"] = false;
    $result["message"] = "No DB connect";
    exit(json_encode($result));
}
$db_link->query("SET NAMES utf8;");

function canAddServices($order_id) {
    global $db_link; // объект PDO
    $stmt = $db_link->prepare("SELECT `paid` FROM `shop_orders` WHERE `id` = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    if (!$order) {
        return false; // заказ не найден – лучше запретить
    }
    // Если оплачен полностью (paid == 1) – запрещаем, иначе разрешаем
    return ($order['paid'] != 1);
}

?>

<script>
	var canAddItems = true;
	var canChangeTimeTo = true;
	<?php if (!canAddServices($_GET["order_id"])): ?>
	canAddItems = false; // Блокируем добавление товаров
	canChangeTimeTo = false;
	<?php endif; ?>
	
	<?php if (!canAddServices($_GET["order_id"])): ?>
	canChangeTimeTo = false;
	<?php endif; ?>
	
	var canAddServices = true;
	<?php if (!canAddServices($_GET["order_id"])) : ?>
		canAddServices = false;
	<?php endif; ?>
</script>

<?php
$workerId  = 1;

$select_worker_id = $db_link->prepare("SELECT `id` FROM `car_service_personal` WHERE `user_id` = ?;");
$select_worker_id->execute([DP_User::getUserId()]);
$res_select_worker_id = $select_worker_id->fetch(PDO::FETCH_ASSOC);

$workerId = $res_select_worker_id["id"];

?> 

<div>
<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/content/carservice/widgets/worker_calendar_widget.php');
?>
</div>
<?php
$select_customer_id = $db_link->prepare("SELECT * FROM `shop_orders` WHERE `id` = ?;");
$select_customer_id->execute([$_GET["order_id"]]);
$res_customer = $select_customer_id->fetch(PDO::FETCH_ASSOC);

$customer_id = $res_customer["user_id"];

require_once($_SERVER["DOCUMENT_ROOT"]."/content/users/dp_user.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/cp/content/car_service/modules/record_info/record_car_info_module.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/cp/content/car_service/modules/record_info/record_info_module.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/cp/content/car_service/modules/record_info/records_table_html.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/cp/content/car_service/modules/record_info/records_table_data_html.php");

$order_id = (int)$_GET['order_id'];
$office_id = $res_customer["office_id"]; // из данных заказа
$group_id = DP_User::getUserGroup($customer_id); // из профиля пользователя

$user_id = $customer_id; // ID покупателя
require_once($_SERVER['DOCUMENT_ROOT'] . '/content/carservice/widgets/add_order_items_widget.php');

$select_items = $db_link->prepare("SELECT * FROM `shop_orders_items` WHERE `order_id` = ?;");
$select_items->execute([$_GET["order_id"]]);

$select_items_res = $select_items->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Товарные позиции</h2>

<?php foreach ($select_items_res as $item) : ?>

<div style="display: flex;">
	<div><?=$item["t2_name"];?></div>
	<div><?=$item["t2_article"];?></div>
	<div><?=$item["t2_manufacturer"];?></div>
	<div><?=$item["count_need"];?></div>
	<div><?=$item["price"];?></div>
</div>
<?php endforeach; ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<button class="btn btn-success" data-toggle="modal" data-target="#modal_part_search"><i class="fa fa-list"></i> Проценка</button>
<div class="modal fade" id="modal_part_search" role="dialog">
<div style="position: static; top: 0; right: 0; bottom: 0; left: 0; z-index: 91050;">
    <div class="modal-dialog modal-fullscreen-xl-down modal-dialog-centered">
		<div class="modal-content">
		
			<div class="modal-header" style="padding: 0px 15px; background: #f5f5f5; border: none; border-bottom: 1px solid #ddd;">
				<button style="margin-top: 8px;" type="button" class="close" data-dismiss="modal">&times;</button>
				<h3><b>Проценка</b></h3>
			</div>
			
			<div class="modal-body" style="padding:0; padding-top:50px;">
				<div class="embed-responsive- embed-responsive-16by9-">
					<?php
					if($DP_Config->chpu_search_on)
					{
					?>
					<iframe id="iframe_modal_part_search" style="border: none;" class="embed-responsive-item" loading="lazy" data-src="/parts/brands/XPLGSU1FQWVVQNFSYSJRUUI8LEYUF?cp=1&cp_order_id=<?php echo $order_id; ?>" src="about:blank" ></iframe>
					<?php
					}else
					{
					?>
					<iframe id="iframe_modal_part_search" style="border: none;" class="embed-responsive-item" loading="lazy" data-src="/shop/part_search?article=XPLGSU1FQWVVQNFSYSJRUUI8LEYUF&cp=1&cp_order_id=<?php echo $order_id; ?>" src="about:blank" ></iframe>
					<?php
					}
					?>
				</div>
			</div>
			
		</div>
    </div>
</div>
</div>


<script>
var screen_width = screen.width;
var screen_height = screen.height;

// Событие - открыто модальное окно проценки по нажатию кнопки
$('#modal_part_search').on('show.bs.modal', function() 
{
	
	<?php
	if((int)$paid !== 0)
	{
	?>
	alert('Заказ должен быть не оплачен для добавления в него позиций из проценки! Отменить оплату заказа, добавьте позиции, заново проведите оплату.');
	$('#modal_part_search').close();
	return;
	<?php
	}
	?>
	
	var iframe = $("#iframe_modal_part_search");
    iframe.attr("src", iframe.data("src")); 
	
	if(screen_width > 1600){
		screen_width = 1600;
	}
	if(screen_height > 1600){
		screen_height = 1600;
	}

	$("#modal_part_search .modal-dialog").width(screen_width - 150);
	$("#modal_part_search .modal-dialog").height(screen_height - 300);

	$("iframe").width($("#modal_part_search .modal-dialog").width() );
	$("iframe").height($("#modal_part_search .modal-dialog").height() );

	if(screen.width < 900)
	{
		screen_width = screen.width;
		screen_height = screen.height;

		var ori = window.orientation;
		screen_width = (ori==90 || ori==-90) ? screen.height : screen.width;
		screen_height = (ori==90 || ori==-90) ? screen.width : screen.height;
		
		$("#modal_part_search .modal-dialog").width(screen_width - 0);
		$("#modal_part_search .modal-dialog").height(screen_height - 0);
		
		$("#modal_part_search .modal-dialog").css('margin', 0);
		$("#modal_part_search .modal-dialog").css('padding', '15px');

		$("#modal_part_search .modal-body").css('margin', 0);
		$("#modal_part_search .modal-body").css('padding', '15px');

		$("iframe").width($("#modal_part_search .modal-dialog").width() -30);
		$("iframe").height($("#modal_part_search .modal-dialog").height() -230);
	}

	$(window).bind( 'orientationchange', function(e)
	{
		if(window.orientation == 90)
		{
			alert(screen.width);
			$("#modal_part_search .modal-dialog").width(screen.width - 0);
			$("#modal_part_search .modal-dialog").height(screen.height - 0);
			
			$("iframe").width($("#modal_part_search .modal-dialog").width());
			$("iframe").height($("#modal_part_search .modal-dialog").height());
		}
		else
		{
			$("#modal_part_search .modal-dialog").width(screen.width - 0);
			$("#modal_part_search .modal-dialog").height(screen.height - 0);
			
			$("iframe").width($("#modal_part_search .modal-dialog").height());
			$("iframe").height($("#modal_part_search .modal-dialog").width());
		}
	});
});  

</script>