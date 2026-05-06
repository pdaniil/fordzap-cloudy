<?php

class ABCPOrder {
	
	public $order = array();
	
	public function __construct( ABCPSAO $abcp_sao, $order_item, $db_link  ) {
		
		$query = $db_link->prepare("SELECT `connection_options` FROM `shop_storages` WHERE `id` = ?;");
		
		$query->execute( array( $order_item["t2_storage_id"] ) );
		$result = $query->fetch();
		
		echo "dates: \n";
		$dates		= $abcp_sao->getShipmentDates();
		echo print_r($dates, true)."\n";
		echo "\n";
		
		$connection_options = json_decode( $result["connection_options"], true);
		$payments	= $connection_options['payment_id'];
		$shipments	= $connection_options['shipment_id'];
		$offices	=  $connection_options['office_id'];
		$addresses	= $connection_options['address_id'];
		$date_id		= $dates[1]['date'];
	
		 
		
		
		$this->order = array ( 'userlogin' => $abcp_sao->login,
								'userpsw' => $abcp_sao->password,
								'positions' => array(),
								'paymentMethod' => $payments,
								'shipmentMethod' => $shipments,
								'shipmentOffice' => $offices,
								'shipmentAddress' => $addresses,
								'shipmentDate' => $date_id,
								'comment' => '');
								
		
		
	}
	
	public function addPosition ( array $position ) {
		
		if ( ! empty ( $this->order ) ) {
			
			$this->order['positions'][] = $position;
			
		}
		
	}
	
	public function getOrderData () {
		
		return $this->order;
		
	}
	
}

?>