<?php 
	$isset_customer = $customer_id > 0;
	
	$cars_arr = [];
	
	if ($isset_customer) {
		$select = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ?;");
		$select->execute([$customer_id]);
		
		$res = $select->fetchAll(PDO::FETCH_ASSOC);
		$cars_arr = $res;
	}
	
	
?>   