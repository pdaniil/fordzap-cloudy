<?php

//Скрипт для формирования HTML товароного чека

defined('_INTASK_') or die('No access');





/*

echo '<pre>';

var_dump($parameters_values);

echo '</pre>';

*/





//Проверяем права на печать

if(DP_User::isAdmin())

{

	//Вызов со стороны админа

}

else

{

	//Вызов со стороны обычноного пользователя

	if($parameters_values['button_visible_for_customer'] == 0)

	{

		//Не могут печатать

		$answer = array();

		$answer["status"] = false;

		$answer["message"] = "Forbiden";

		exit(json_encode($answer));

	}

}





//Получаем корзину покупателя

if($user_id > 0)

{

	//Поля для авторизованного пользователя

	$session_id = 0;

}

else

{

	//Поля для НЕ авторизованного пользователя

	$session_record = DP_User::getUserSession();

	if($session_record == false)

	{

		$result = array();

		$result["status"] = false;

		$result["code"] = "incorrect_session";

		$result["message"] = "Ошибка сессии";

		exit(json_encode($result));

	}

	$session_id = $session_record["id"];

}



//Список записей корзины для этого пользователя

$cart_records = array();



//Получаем содержимое корзины из базы данных

$cart_records_query = $db_link->prepare('SELECT * FROM `shop_carts` WHERE `user_id` = ? AND `session_id` = ? AND `checked_for_order` = 1;');

$cart_records_query->execute( array($user_id, $session_id) );

while($cart_record = $cart_records_query->fetch() )

{

	array_push($cart_records, $cart_record);

}

if(isset($_GET['new'])){
	$officeId = 1;
	if(isset($_COOKIE['my_city'])){
		$stmt = $db_link->prepare('SELECT * FROM `shop_geo` WHERE `id` = ?;');
		$node = $stmt->execute(array((int)$_COOKIE["my_city"])) ? $stmt->fetch(PDO::FETCH_ASSOC) : false;
		if($node){
			$officeQuery = $db_link->prepare("SELECT * FROM shop_offices_geo_map WHERE geo_id=? LIMIT 1");
			$office = $officeQuery->execute(array($node['id'])) ? $officeQuery->fetch(PDO::FETCH_ASSOC) : false;
			if($office){
				$officeId = $office['office_id'];
			}
		}
	}
	require_once($_SERVER['DOCUMENT_ROOT'] . '/content/shop/print_docs/service/check.php');
	$cartData = array(
		'created' => time(),
		'records' => array_column($cart_records, 'id'),
		'office_id' => $officeId
	);
	header('Location: /check/' . encryptItems($cartData));
}

/*

echo '<pre>';

var_dump($cart_records);

echo '</pre>';

*/



if(empty($cart_records))

{

	$answer = array();

	$answer["status"] = false;

	$answer["message"] = "No such order";

    exit(json_encode($answer));

}



//Формируем HTML

ob_start();

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 5.0 Transitional//EN">

<HTML>

<HEAD>

<META HTTP-EQUIV="Content-Type" CONTENT="text/html; CHARSET=utf-8">

<TITLE></TITLE>

<STYLE TYPE="text/css">

body { background: #ffffff; margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 8pt; font-style: normal; }

tr.R0{ height: 15px; }

tr.R0 td.R3C1{ border-left: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R0 td.R7C1{ font-family: DejaVu Sans, sans-serif; font-size: 8pt; font-style: normal; text-align: left; vertical-align: middle; border-left: #000000 1px solid; border-top: #ffffff 0px none; border-bottom: #000000 1px solid; }

tr.R0 td.R9C1{ font-family: DejaVu Sans, sans-serif; font-size: 14pt; font-style: normal; font-weight: bold; vertical-align: middle; }

tr.R1{ height: 17px; }

tr.R1 td.R19C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; }

tr.R1 td.R19C5{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; vertical-align: top; }

tr.R1 td.R1C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-right: #000000 1px solid; }

tr.R1 td.R1C19{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R1 td.R1C22{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #ffffff 0px none; border-right: #000000 1px solid; }

tr.R1 td.R21C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: middle; border-left: #000000 2px solid; border-top: #000000 2px solid; }

tr.R1 td.R21C2{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 2px solid; }

tr.R1 td.R21C6{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; text-align: center; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 2px solid; border-right: #000000 2px solid; }

tr.R1 td.R24C6{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; text-align: right; vertical-align: top; }

tr.R1 td.R27C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; }

tr.R1 td.R36C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; }

tr.R1 td.R36C19{ text-align: right; border-bottom: #000000 1px solid; }

tr.R1 td.R36C7{ border-bottom: #000000 1px solid; }

tr.R1 td.R4C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: middle; border-left: #000000 1px solid; border-top: #000000 1px solid; }

tr.R1 td.R4C19{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R1 td.R4C22{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R1 td.R4C3{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: middle; border-top: #000000 1px solid; border-right: #000000 1px solid; }

tr.R1 td.R5C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; }

tr.R11{ height: 9px; }

tr.R11 td.R11C1{ border-bottom: #000000 2px solid; }

tr.R11 td.R23C1{ border-top: #000000 2px solid; }

tr.R13{ height: 34px; }

tr.R13 td.R13C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; }

tr.R13 td.R13C5{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; vertical-align: top; }

tr.R16{ height: 18px; }

tr.R16 td.R16C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; }

tr.R16 td.R16C5{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; font-weight: bold; vertical-align: top; }

tr.R2{ height: 16px; }

tr.R2 td.R2C19{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R2 td.R2C22{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #ffffff 0px none; border-bottom: #000000 1px solid; border-right: #000000 1px solid; }

tr.R2 td.R30C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; }

tr.R22{ height: 29px; }

tr.R22 td.R22C1{ text-align: center; vertical-align: top; overflow: hidden;border-left: #000000 2px solid; border-top: #000000 1px solid; }

tr.R22 td.R22C2{ text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; }

tr.R22 td.R22C3{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; }

tr.R22 td.R22C4{ text-align: left; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; }

tr.R22 td.R22C6{ text-align: right; vertical-align: top; border-left: #000000 1px solid; border-top: #000000 1px solid; border-right: #000000 2px solid; }

tr.R29{ height: 10px; }

tr.R29 td.R29C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; }

tr.R31{ height: 31px; }

tr.R31 td.R31C1{ font-family: DejaVu Sans, sans-serif; font-size: 9pt; font-style: normal; text-align: left; }

table {table-layout: fixed; padding: 0px; padding-left: 2px; vertical-align:bottom; border-collapse:collapse;width: 100%; font-family: DejaVu Sans, sans-serif; font-size: 8pt; font-style: normal; }

td { padding: 0px; padding-left: 2px; overflow:hidden; }

</STYLE>

</HEAD>

<BODY STYLE="background: #ffffff; margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 8pt; font-style: normal; ">

<TABLE style="width:100%; height:0px; " CELLSPACING=0>

<COL WIDTH=7>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=26>

<COL WIDTH=15>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=17>

<COL WIDTH=16>

<COL WIDTH=16>

<COL WIDTH=17>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=23>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=12>

<COL WIDTH=13>

<COL>

<TR CLASS=R0>

<TD><SPAN></SPAN></TD>

<TD CLASS="R9C1" COLSPAN=12 ROWSPAN=2 STYLE="text-align:center;">

<SPAN STYLE="white-space:nowrap;font-size:26px;"><?php echo (!empty($parameters_values['site_name']))?'<br>'.$parameters_values['site_name'].'':''; ?></SPAN>

<SPAN STYLE="white-space:nowrap;font-size:12px;"><?php echo (!empty($parameters_values['seller']))?'<br>'.$parameters_values['seller'].'':''; ?></SPAN>

<SPAN STYLE="white-space:nowrap;font-size:12px;"><?php echo (!empty($parameters_values['phone']))?'<br>'.$parameters_values['phone'].'':''; ?></SPAN>

</TD>

<TD CLASS="R9C1" COLSPAN=19 ROWSPAN=2 STYLE=""><SPAN STYLE="border-bottom:3px solid #000; display:block; padding-left:10px; text-align:right;">Цены на запчасти от <?php echo get_date(time()); ?> г.</SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>

<TR CLASS=R0>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>

<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1" COLSPAN=32><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>

<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>

<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>

<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>















<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>

</TABLE>

<TABLE style="width:100%; height:0px; " CELLSPACING=0>

<COL WIDTH=7>

<COL WIDTH=32>

<COL WIDTH=70>

<COL WIDTH=258>

<COL WIDTH=54>

<COL WIDTH=42>

<COL WIDTH=87>

<COL WIDTH=99>

<COL>

<TR CLASS=R1>

<TD><SPAN></SPAN></TD>

<TD CLASS="R21C1"><SPAN STYLE="white-space:nowrap;max-width:0px;">№</SPAN></TD>

<TD CLASS="R21C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">Артикул</SPAN></TD>

<TD CLASS="R21C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">Товар</SPAN></TD>

<TD CLASS="R21C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">Кол-во</SPAN></TD>

<TD CLASS="R21C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">Срок</SPAN></TD>

<TD CLASS="R21C2"><SPAN STYLE="white-space:nowrap;max-width:0px;">Цена</SPAN></TD>

<TD CLASS="R21C6"><SPAN STYLE="white-space:nowrap;max-width:0px;">Сумма</SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>



<?php

$count_items = 0;//Счетчик позиций

$price_sum_total_num = 0;//Сумма ИТОГО (цифра без форматирования)

foreach( $cart_records as $order_item )

{

	$count_items++;//Счетчик позиций

	

	$price_sum_total_num = $price_sum_total_num + ($order_item["price"] * $order_item["count_need"]);//Сумма ИТОГО

	

	$time_to_exe = $order_item["t2_time_to_exe"];

	if($order_item["t2_time_to_exe"] < $order_item["t2_time_to_exe_guaranteed"]){

		$time_to_exe = $order_item["t2_time_to_exe"] .'-'. $order_item["t2_time_to_exe_guaranteed"];

	}

	$time_to_exe .= ' дн.';

	

	if($parameters_values['article_hidden'] == 1){

		$order_item["t2_article"] = '*******';

	}

	

	?>

	<TR CLASS=R22>

	<TD><SPAN></SPAN></TD>

	<TD CLASS="R22C1"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $count_items; ?></SPAN></TD>

	<TD CLASS="R22C2"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo trim($order_item["t2_article"]); ?></SPAN></TD>

	<TD CLASS="R22C2"><?php echo trim($order_item["t2_manufacturer"]) .'. '. trim($order_item["t2_name"]); ?></TD>

	<TD CLASS="R22C3" STYLE="text-align:center;"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $order_item["count_need"]; ?></SPAN></TD>

	<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $time_to_exe; ?></SPAN></TD>

	<TD CLASS="R22C3"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo number_format($order_item["price"], 2, '.', ' '); ?></SPAN></TD>

	<TD CLASS="R22C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo number_format(($order_item["price"] * $order_item["count_need"]), 2, '.', ' '); ?></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	<?php

}

$price_sum_total = number_format($price_sum_total_num, 2, '.', ' ');

?>



</TABLE>

<TABLE style="width:100%; height:0px; " CELLSPACING=0>

<COL WIDTH=7>

<COL WIDTH=32>

<COL WIDTH=75>

<COL WIDTH=190>

<COL WIDTH=54>

<COL WIDTH=42>

<COL WIDTH=151>

<COL WIDTH=98>

<COL>

<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R23C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>





<?php

//Обработка НДС

if( $parameters_values["nds"] == "" )

{

	?>

	<TR CLASS=R1>

	<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Итого:</SPAN></TD>

	<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $price_sum_total; ?></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	

	

	<TR CLASS=R1>

	<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Без&nbsp;налога&nbsp;(НДС)</SPAN></TD>

	<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;">-</SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	<?php

}

else

{

	$parameters_values["nds"] = (int)$parameters_values["nds"];

	

	//Получаем значение НДС и сумму без НДС, включенного в сумму:

	$price_sum_total_without_NDS = $price_sum_total_num/(1 + $parameters_values["nds"]/100);

	$NDS = $price_sum_total_num - $price_sum_total_without_NDS;

	?>

	<TR CLASS=R1>

	<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Сумма с НДС:</SPAN></TD>

	<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $price_sum_total; ?></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	

	<TR CLASS=R1>

	<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Сумма без НДС:</SPAN></TD>

	<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo number_format($price_sum_total_without_NDS, 2, '.', ' '); ?></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	

	

	<TR CLASS=R1>

	<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">НДС <?php echo $parameters_values["nds"]; ?>%:</SPAN></TD>

	<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo number_format($NDS, 2, '.', ' '); ?></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	<?php

}

?>





<TR CLASS=R1>

<TD CLASS="R24C6" COLSPAN=7><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;к&nbsp;оплате:</SPAN></TD>

<TD CLASS="R24C6"><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $price_sum_total; ?></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>





</TABLE>

<TABLE style="width:100%; height:0px; " CELLSPACING=0>

<COL WIDTH=7>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=26>

<COL WIDTH=15>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=17>

<COL WIDTH=16>

<COL WIDTH=16>

<COL WIDTH=17>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=23>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=21>

<COL WIDTH=12>

<COL WIDTH=13>

<COL>

<TR CLASS=R1>

<TD><SPAN></SPAN></TD>

<TD CLASS="R27C1" COLSPAN=32><SPAN STYLE="white-space:nowrap;max-width:0px;">Всего&nbsp;наименований&nbsp;<?php echo $count_items; ?>,&nbsp;на&nbsp;сумму&nbsp;<?php echo $price_sum_total; ?>&nbsp;<?php echo $currency["caption_short"]; ?>.</SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>

<TR CLASS=R1>

<TD><SPAN></SPAN></TD>

<TD CLASS="R19C5" COLSPAN=31><?php echo num2str($price_sum_total_num); ?></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>

<TR CLASS=R29>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R29C1"><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:10px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:10px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>





















<TR CLASS=R11>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD CLASS="R11C1"><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="position:relative; height:9px;width: 100%; overflow:hidden;"><SPAN></SPAN></DIV></TD>

<TD><DIV STYLE="width:100%;height:9px;overflow:hidden;">&nbsp;</DIV></TD>

</TR>

<TR>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>

<TR>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>

<TR CLASS=R1>

<TD><SPAN></SPAN></TD>

<TD CLASS="R36C1" COLSPAN=6><SPAN STYLE="white-space:nowrap;max-width:0px;"><?php echo $parameters_values["dl_name"]; ?></SPAN></TD>

<TD CLASS="R36C7"><SPAN></SPAN></TD>

<TD CLASS="R36C7"><SPAN></SPAN></TD>

<TD CLASS="R36C7"><SPAN></SPAN></TD>

<TD CLASS="R36C7" COLSPAN=9>

	<SPAN>

		<?php

		if( $parameters_values["signature_scan"] != "" )

		{

			if( file_exists($_SERVER["DOCUMENT_ROOT"]."/content/files/images/".$parameters_values["signature_scan"]) )

			{

				?>

				<img src="/content/files/images/<?php echo $parameters_values["signature_scan"]; ?>" style="max-width:150px;max-height:100px;" />

				<?php

			}

		}

		?>

	</SPAN>

</TD>

<TD CLASS="R36C19" COLSPAN=14><?php echo $parameters_values["dl_fio"]; ?></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD></TD>

</TR>















<TR>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>

<TR>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>



<TR>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD><SPAN></SPAN></TD>

<TD>&nbsp;</TD>

</TR>

















<?php

if( $parameters_values["explanation"] != "" )

{

	

	?>

	<TR CLASS=R31>

	<TD><SPAN></SPAN></TD>

	<TD CLASS="R31C1" COLSPAN=32><?php echo str_replace("\n","<br>",$parameters_values["explanation"]); ?></TD>

	<TD><SPAN></SPAN></TD>

	<TD><SPAN></SPAN></TD>

	<TD></TD>

	</TR>

	<?php

}

?>





</TABLE>







</BODY>

</HTML>

<?php

$HTML = ob_get_contents();

ob_end_clean();

?>