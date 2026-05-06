<?php 
	
	function get_cars_info_by_user_id($user_id) {
		global $db_link;
		global $DP_Config;
		
		$select = $db_link->prepare("SELECT * FROM `shop_docpart_garage` WHERE `user_id` = ?;");
		$select->execute([$user_id]);
		
		$res = $select->fetchAll(PDO::FETCH_ASSOC);
		return $res;
	}
	
	$user_info_arr = [];
	
	if (DP_User::getUserId() > 0) {
			
		$arr_users = [];

		$selest_users = $db_link->prepare("SELECT *, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'name' AND `user_id` = `users`.`user_id`) as `name`, (SELECT `data_value` FROM `users_profiles` WHERE `data_key` = 'surname' AND `user_id` = `users`.`user_id`) as `surname` FROM `users` WHERE `user_id` = ?;");
		$selest_users->execute([DP_User::getUserId()]);
		$arr_users = $selest_users->fetch(PDO::FETCH_ASSOC);
		
		$user_info_arr = [
			"user_id" => $arr_users["user_id"],
			"email" => $arr_users["email"],
			"surname" => $arr_users["surname"],
			"phone" => $arr_users["phone"],
			"cars" => get_cars_info_by_user_id($arr_users["user_id"]),
			"fio" => $arr_users["surname"] . " " . $arr_users["name"]
		];	
		
	}
	
?>  