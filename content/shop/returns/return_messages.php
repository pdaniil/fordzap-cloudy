<?php
defined('_ASTEXE_') or die('No access');
?>
<!-- Переписка с покупателем -->
<p class="lead">Переписка с продавцом</p>
<div>
    <div class="chat_block" id="chat_block">
    </div>

    <br>
    Новое сообщение:
    <textarea id="new_message_area"></textarea>
    <button class="btn btn-ar btn-primary" onclick="sendMessage();">Отправить</button>
</div>
<script>
    // --------------------------------------------------------------------------
    //Получить сообщения по заказу
    function getOrderMessages() {
        jQuery.ajax({
            type: "GET",
            async: true,
            url: "/content/shop/messager/ajax_get_order_messages.php",
            dataType: "json",//Тип возвращаемого значения
            data: "return_id=<?php echo $_GET["return_id"]; ?>" + "&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function (answer) {
                var html = "";
                for (var i = 0; i < answer.length; i++) {
                    var class_str = "bubble";
                    var sender = "Покупатель";
                    if (answer[i].is_customer == false) {
                        class_str += "2";
                        sender = "Продавец";
                    }
                    html += "<div class=\"" + class_str + "\">" + sender + " " + answer[i].time + "<br>" + answer[i].text + "</div>";
                }
                if (html == "") html = "<div align=\"center\">Сообщений в данном заказе нет</div>";
                document.getElementById("chat_block").innerHTML = html;

                document.getElementById("chat_block").scrollTop = document.getElementById("chat_block").scrollHeight;
            }
        });
    }

    // --------------------------------------------------------------------------
    //Отправить сообщение
    function sendMessage() {
        var text = document.getElementById("new_message_area").value;
        if (text == "") {
            alert("Поле для сообщение пустое");
            return;
        }

        jQuery.ajax({
            type: "GET",
            async: true,
            url: "/content/shop/messager/ajax_send_message.php",
            dataType: "json",//Тип возвращаемого значения
            data: "return_id=<?php echo $_GET["return_id"]; ?>&text=" + encodeURI(text) + "&csrf_guard_key=<?php echo $user_session["csrf_guard_key"]; ?>",
            success: function (answer) {
                if (answer == true) {
                    document.getElementById("new_message_area").value = "";
                    getOrderMessages();
                } else {
                    alert("Ошибка отправки сообщения");
                }
            }
        });
    }

    // --------------------------------------------------------------------------
    getOrderMessages();//Запрашиваем переписку по заказу

    setInterval(function () {
        getOrderMessages();
    }, 300000);
</script>