<?php 
$user_id = DP_User::getUserId();
?>
<div class="alert alert-info" style="background-color: #f5f5f5; border: solid 1px #ddd; margin: 0; border-left: 0; border-right: 0; border-top: 0;">
    	<div class="container">
    		<h4><strong><i class="fa fa-bullhorn"></i> Объявление</strong></h4>
    		<div>Сайт в разработке!</div>
    		<a href="javascript:void(0);" onClick="sendToTelegram();"><div>У нас доступен новый телеграм бот!</div></a>
    	
    	</div>
    </div>
    
<script>
function sendToTelegram(){
    
        var user_id = <?=$user_id?>;
        console.log(user_id);
        
        var request_object = new Object();
        request_object.user_id = user_id;
        
    	jQuery.ajax({
			type: "POST",
			async: false,
// 			url: "/modules/telegram/ajax_send_code.php?csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
	        data: "request_object="+encodeURIComponent(JSON.stringify(request_object)),
			url: "/modules/telegram/ajax_send_code.php",
			dataType: "json",//Тип возвращаемого значения
			success: function(answer)
			{
			    console.log(answer);
			    if(answer.status == true)
			    {
			        location = "https://t.me/fordzapbot?start=" + answer.telegram_code;
			    }
			    else{
			        alert("Ошибка");
			    }
			}
		});
}
</script>