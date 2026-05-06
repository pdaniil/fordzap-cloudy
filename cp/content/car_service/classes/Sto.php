<?php 

class STO {
	public $id;
	public $operating_mode;
	public $cation;
	public $id_tree_list_services;
	public $address;
	public $time_from;
	public $time_to;
	public $organization;
	public $inn;
	public $phone;
	
	public function __construct($arr_data = null) {
		if (is_array($arr_data)) {
			foreach ($arr_data as $key => $value) {
				$this->$key = $value;
			}
		}
	}
	
	public function create_sto() {
		global $db_link;
		
		$sql = "INSERT INTO `car_services` (`operating_mode`, `caption`, `id_tree_list_services`, `address`,`time_from`, `time_to`,`organization`,`inn`,`phone`) VALUES (?, ?, ?, ?, ?, ?,?,?,?)";
		$data_arr = [$this->operating_mode, $this->caption, $this->id_tree_list_services, $this->address, $this->time_from, $this->time_to, $this->organization, $this->inn, $this->phone];
		
		$result = pdoQuery($sql, $data_arr);
		
		if ($result) {
			$this->id = $db_link->lastInsertId();
		}
		else {
			exitFunction(["status" => 500, "message" => "Возникли ошибки при создании новой записи."]);
		}
	}
	
	
	public function update_sto() {
		global $db_link;
		
		$sql = "UPDATE `car_services` SET `operating_mode` = ?, `caption` = ?, `id_tree_list_services` = ?, `address` = ?, `time_from` = ?, `time_to` = ?, `organization` = ?, `inn` = ?, `phone` = ? WHERE `id` = ?;";
		$data_arr = [$this->operating_mode, $this->caption, $this->id_tree_list_services, $this->address, $this->time_from, $this->time_to, $this->organization, $this->inn, $this->phone, $this->id];
		
		$result = pdoQuery($sql, $data_arr);
		
		if (!$result) {
			exitFunction(["status" => 500, "message" => "Возникли ошибки при обновлении записи."]);
		}
		
	}
	
	public function delete_sto() {
		global $db_link;
		
		$sql = "DELETE FROM `car_services` WHERE `id` = ?;";
		$data_arr = [$this->id];
		
		$result = pdoQuery($sql, $data_arr);
		
		if (!$result) {
			exitFunction(["status" => 500, "message" => "Возникли ошибки при удалении записи."]);
		}
	}
	
	
	
	
}

function pdoQuery($sql, $data_arr, $return = null) {
	global $db_link;
	
	$query = $db_link->prepare($sql);
	$result_exec = $query->execute($data_arr);
	
	if ($return) {
		return $query->fetchAll(PDO::FETCH_ASSOC);
	}
	else {
		return $result_exec;
	}
}

?>